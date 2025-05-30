<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Family;
use App\Models\RankSetting;
use App\Models\FamilyCriterion;
use App\Models\Province;
use App\Models\City;
use App\Models\Organization;

class FamilyCriteria extends Component
{
    use WithPagination;

    // فیلترهای جستجو
    public $search = '';
    public $filterProvince = '';
    public $filterCity = '';
    public $filterCharity = '';
    public $filterRankRange = '';
    public $filterCriteria = '';
    public $sortField = 'calculated_rank';
    public $sortDirection = 'desc';

    // مودال مدیریت معیارها
    public $showCriteriaModal = false;
    public $selectedFamily = null;
    public $familyCriteria = [];
    public $availableCriteria = [];

    // داده‌های پایه
    public $provinces = [];
    public $cities = [];
    public $organizations = [];
    public $rankSettings = [];

    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'filterProvince' => ['except' => ''],
        'filterCity' => ['except' => ''],
        'filterCharity' => ['except' => ''],
        'filterRankRange' => ['except' => ''],
        'filterCriteria' => ['except' => ''],
        'sortField' => ['except' => 'calculated_rank'],
        'sortDirection' => ['except' => 'desc'],
    ];

    public function mount()
    {
        $this->provinces = Province::orderBy('name')->get();
        $this->cities = City::orderBy('name')->get();
        $this->organizations = Organization::where('type', 'charity')->orderBy('name')->get();
        $this->rankSettings = RankSetting::active()->ordered()->get();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterProvince()
    {
        $this->resetPage();
        $this->filterCity = ''; // ریست کردن شهر هنگام تغییر استان
    }

    public function updatingFilterCity()
    {
        $this->resetPage();
    }

    public function updatingFilterCharity()
    {
        $this->resetPage();
    }

    public function updatingFilterRankRange()
    {
        $this->resetPage();
    }

    public function updatingFilterCriteria()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
        $this->resetPage();
    }

    public function openCriteriaModal($familyId)
    {
        $this->selectedFamily = Family::with(['familyCriteria.rankSetting'])->findOrFail($familyId);
        
        // دریافت معیارهای فعلی خانواده
        $currentCriteria = $this->selectedFamily->familyCriteria->pluck('rank_setting_id')->toArray();
        
        // آماده‌سازی آرایه معیارها برای نمایش
        $this->familyCriteria = [];
        foreach ($this->rankSettings as $setting) {
            $this->familyCriteria[$setting->id] = in_array($setting->id, $currentCriteria);
        }
        
        $this->showCriteriaModal = true;
    }

    public function closeCriteriaModal()
    {
        $this->showCriteriaModal = false;
        $this->selectedFamily = null;
        $this->familyCriteria = [];
    }

    public function toggleCriteria($rankSettingId, $hasIt)
    {
        if (!$this->selectedFamily) {
            return;
        }

        try {
            if ($hasIt) {
                // اضافه کردن معیار
                $this->selectedFamily->addCriteria($rankSettingId);
            } else {
                // حذف معیار
                $this->selectedFamily->removeCriteria($rankSettingId);
            }

            // محاسبه مجدد رتبه
            $this->selectedFamily->calculateRank();
            
            session()->flash('success', 'معیارهای خانواده به‌روزرسانی شد.');
        } catch (\Exception $e) {
            session()->flash('error', 'خطا در به‌روزرسانی معیارها: ' . $e->getMessage());
        }
    }

    public function saveCriteria()
    {
        if (!$this->selectedFamily) {
            return;
        }

        try {
            foreach ($this->familyCriteria as $rankSettingId => $hasIt) {
                if ($hasIt) {
                    $this->selectedFamily->addCriteria($rankSettingId);
                } else {
                    $this->selectedFamily->removeCriteria($rankSettingId);
                }
            }

            // محاسبه مجدد رتبه
            $this->selectedFamily->calculateRank();
            
            $this->closeCriteriaModal();
            session()->flash('success', 'معیارهای خانواده با موفقیت ذخیره شد.');
        } catch (\Exception $e) {
            session()->flash('error', 'خطا در ذخیره معیارها: ' . $e->getMessage());
        }
    }

    public function recalculateRank($familyId)
    {
        try {
            $family = Family::findOrFail($familyId);
            $newRank = $family->calculateRank();
            
            session()->flash('success', "رتبه خانواده محاسبه شد: {$newRank}");
        } catch (\Exception $e) {
            session()->flash('error', 'خطا در محاسبه رتبه: ' . $e->getMessage());
        }
    }

    public function recalculateAllRanks()
    {
        try {
            $families = Family::all();
            $updatedCount = 0;
            
            foreach ($families as $family) {
                $family->calculateRank();
                $updatedCount++;
            }
            
            session()->flash('success', "رتبه {$updatedCount} خانواده محاسبه شد.");
        } catch (\Exception $e) {
            session()->flash('error', 'خطا در محاسبه رتبه‌ها: ' . $e->getMessage());
        }
    }

    public function getCitiesForProvince()
    {
        if ($this->filterProvince) {
            return City::where('province_id', $this->filterProvince)->orderBy('name')->get();
        }
        return collect();
    }

    public function render()
    {
        $query = Family::query()->with([
            'province',
            'city', 
            'organization',
            'members' => function($q) {
                $q->where('is_head', true);
            },
            'familyCriteria.rankSetting'
        ]);

        // فیلتر جستجو
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('family_code', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%')
                  ->orWhereHas('members', function ($memberQuery) {
                      $memberQuery->where('first_name', 'like', '%' . $this->search . '%')
                                  ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                  ->orWhere('national_code', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // فیلتر استان
        if ($this->filterProvince) {
            $query->where('province_id', $this->filterProvince);
        }

        // فیلتر شهر
        if ($this->filterCity) {
            $query->where('city_id', $this->filterCity);
        }

        // فیلتر خیریه
        if ($this->filterCharity) {
            $query->where('charity_id', $this->filterCharity);
        }

        // فیلتر بازه رتبه
        if ($this->filterRankRange) {
            switch ($this->filterRankRange) {
                case 'very_high':
                    $query->byRankRange(80, 100);
                    break;
                case 'high':
                    $query->byRankRange(60, 79);
                    break;
                case 'medium':
                    $query->byRankRange(40, 59);
                    break;
                case 'low':
                    $query->byRankRange(20, 39);
                    break;
                case 'very_low':
                    $query->byRankRange(0, 19);
                    break;
                case 'unranked':
                    $query->whereNull('calculated_rank');
                    break;
            }
        }

        // فیلتر معیار خاص
        if ($this->filterCriteria) {
            $rankSetting = RankSetting::find($this->filterCriteria);
            if ($rankSetting) {
                $query->withCriteria($rankSetting->key);
            }
        }

        // مرتب‌سازی
        $query->orderBy($this->sortField, $this->sortDirection);

        $families = $query->paginate(15);

        return view('livewire.admin.family-criteria', [
            'families' => $families,
            'provinces' => $this->provinces,
            'cities' => $this->filterProvince ? $this->getCitiesForProvince() : $this->cities,
            'organizations' => $this->organizations,
            'rankSettings' => $this->rankSettings,
        ]);
    }
} 