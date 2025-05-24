<?php

namespace App\Livewire\Charity;

use App\Models\Family;
use App\Models\Region;
use App\Models\Member;
use App\Models\Charity;
use App\Models\Province;
use App\Models\City;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class FamilySearch extends Component
{
    use WithPagination;
    
    public $search = '';
    public $status = '';
    public $region = '';
    public $charity = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $expandedFamily = null;
    public $familyMembers = [];
    public $regions = [];
    public $provinces = [];
    public $cities = [];
    public $selectedHead = null;
    public $perPage = 15;
    public $province = '';
    public $city = '';
    public $deprivation_rank = '';
    
    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'region' => ['except' => ''],
        'charity' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'perPage' => ['except' => 15],
    ];
    
    public function mount()
    {
        $this->regions = Region::all();
        $this->provinces = Province::all();
        $this->cities = City::all();
    }
    
    public function render()
    {
        $query = Family::query()
            ->with(['region', 'members', 'charity', 'province', 'city']);
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->status !== '') {
            if ($this->status === 'uninsured') {
                $query->whereIn('status', [
                    'pending', 'reviewing', 'approved', 'renewal', 'rejected', 'deleted'
                ]);
            } else {
                $query->where('status', $this->status);
            }
        }
        
        if ($this->province) {
            $query->where('province_id', $this->province);
        }
        
        if ($this->city) {
            $query->where('city_id', $this->city);
        }
        
        if ($this->deprivation_rank) {
            $query->whereHas('province', function($q) {
                if ($this->deprivation_rank === 'high') {
                    $q->whereBetween('deprivation_rank', [1, 3]);
                } elseif ($this->deprivation_rank === 'medium') {
                    $q->whereBetween('deprivation_rank', [4, 6]);
                } elseif ($this->deprivation_rank === 'low') {
                    $q->whereBetween('deprivation_rank', [7, 10]);
                }
            });
        }
        
        if ($this->region) {
            $query->where('region_id', $this->region);
        }
        
        if ($this->charity) {
            $query->where('charity_id', $this->charity);
        }
        
        $query->orderBy($this->sortField, $this->sortDirection);
        
        $families = $query->paginate($this->perPage);
        
        return view('livewire.charity.family-search', [
            'families' => $families,
            'regions' => $this->regions,
            'provinces' => $this->provinces,
            'cities' => $this->cities,
        ]);
    }
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingStatus()
    {
        $this->resetPage();
    }
    
    public function updatingRegion()
    {
        $this->resetPage();
    }
    
    public function updatingCharity()
    {
        $this->resetPage();
    }
    
    public function updatingProvince()
    {
        $this->resetPage();
    }

    public function updatingCity()
    {
        $this->resetPage();
    }

    public function updatingDeprivationRank()
    {
        $this->resetPage();
    }
    
    /**
     * دریافت شماره صفحه فعلی
     * 
     * @return int
     */
    public function getPage()
    {
        return (int) $this->page;
    }
    
    /**
     * رفتن به صفحه بعدی
     * 
     * @return void
     */
    public function nextPage()
    {
        $nextPage = $this->getPage() + 1;
        
        if ($nextPage <= $this->families->lastPage()) {
            $this->gotoPage($nextPage);
        }
    }
    
    /**
     * رفتن به صفحه قبلی
     * 
     * @return void
     */
    public function previousPage()
    {
        $prevPage = $this->getPage() - 1;
        
        if ($prevPage > 0) {
            $this->gotoPage($prevPage);
        }
    }
    
    /**
     * اعمال فیلترها به کوئری
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    private function applyFilters($query)
    {
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->status === 'insured') {
            $query->whereHas('members', function ($q) {
                $q->whereNotNull('insurance_type');
            });
        } elseif ($this->status === 'uninsured') {
            $query->whereDoesntHave('members', function ($q) {
                $q->whereNotNull('insurance_type');
            });
        } elseif ($this->status !== '') {
            $query->where('status', $this->status);
        }
        
        if ($this->region) {
            $query->where('region_id', $this->region);
        }
        
        if ($this->charity) {
            $query->where('charity_id', $this->charity);
        }
        
        $query->orderBy($this->sortField, $this->sortDirection);
    }
    
    /**
     * رفتن به صفحه مشخص با اعتبارسنجی شماره صفحه
     * 
     * @param int $page شماره صفحه
     * @return void
     */
    public function gotoPage($page)
    {
        // محاسبه تعداد کل صفحات
        $query = Family::query()
            ->with(['region', 'members', 'charity']);
        
        // اعمال فیلترها
        $this->applyFilters($query);
        
        $paginator = $query->paginate($this->perPage);
        $lastPage = $paginator->lastPage();
        
        // اطمینان از این‌که صفحه در محدوده معتبر است
        $page = max(1, min(intval($page), $lastPage));
        
        // استفاده از setPage به جای فراخوانی مستقیم
        $this->setPage($page);
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function toggleFamily($familyId)
    {
        if ($this->expandedFamily === $familyId) {
            $this->expandedFamily = null;
            $this->familyMembers = [];
        } else {
            $this->expandedFamily = $familyId;
            
            // بارگذاری کامل اعضای خانواده با تمام اطلاعات و مرتب‌سازی مناسب
            $family = Family::with(['members' => function($query) {
                // مرتب‌سازی: ابتدا سرپرست و سپس به ترتیب ID
                $query->orderBy('is_head', 'desc')
                      ->orderBy('id', 'asc');
            }])->findOrFail($familyId);
            
            // تهیه کالکشن کامل اعضای خانواده
            $this->familyMembers = $family->members;
            
            // تنظیم selectedHead به ID سرپرست فعلی
            foreach ($this->familyMembers as $member) {
                if ($member->is_head) {
                    $this->selectedHead = $member->id;
                    break;
                }
            }
            
            // ارسال رویداد برای اسکرول به موقعیت خانواده باز شده
            $this->dispatch('family-expanded', $familyId);
        }
    }
    
    /**
     * تنظیم سرپرست خانواده
     *
     * @param int $familyId شناسه خانواده
     * @param int $memberId شناسه عضو
     * @return void
     */
    public function setFamilyHead($familyId, $memberId)
    {
        try {
            $family = Family::findOrFail($familyId);
            
            // فقط اگر خانواده تایید نشده باشد، اجازه تغییر سرپرست را بدهیم
            if (!$family->verified_at) {
                // تنظیم متغیر انتخاب شده
                $this->selectedHead = $memberId;
                
                // مدیریت تراکنش برای اطمینان از صحت داده‌ها
                DB::beginTransaction();
                
                // به‌روزرسانی پایگاه داده
                Member::where('family_id', $familyId)->update(['is_head' => false]);
                Member::where('id', $memberId)->update(['is_head' => true]);
                
                DB::commit();
                
                // به‌روزرسانی نمایش بدون بارگیری مجدد کامل
                if ($this->expandedFamily === $familyId && !empty($this->familyMembers)) {
                    // به‌روزرسانی state داخلی بدون بارگیری مجدد
                    foreach ($this->familyMembers as $member) {
                        // فقط وضعیت is_head را تغییر می‌دهیم
                        $member->is_head = ($member->id == $memberId);
                    }
                }
                
                // نمایش پیام موفقیت
                $this->dispatch('show-toast', [
                    'message' => 'سرپرست خانواده با موفقیت تغییر کرد', 
                    'type' => 'success'
                ]);
            } else {
                $this->dispatch('show-toast', [
                    'message' => 'امکان تغییر سرپرست برای خانواده‌های تایید شده وجود ندارد', 
                    'type' => 'error'
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('show-toast', [
                'message' => 'خطا در به‌روزرسانی اطلاعات: ' . $e->getMessage(), 
                'type' => 'error'
            ]);
        }
    }
    
    public function verifyFamily($familyId)
    {
        // بررسی دسترسی کاربر
        if (!Auth::check() || !Gate::allows('verify-family')) {
            $this->dispatch('show-toast', [
                'message' => 'شما اجازه تایید خانواده را ندارید',
                'type' => 'error'
            ]);
            return;
        }
        
        $family = Family::findOrFail($familyId);
        
        // اگر قبلاً تایید شده، اطلاع بدهیم
        if ($family->verified_at) {
            $this->dispatch('show-toast', [
                'message' => 'این خانواده قبلاً تایید شده است',
                'type' => 'error'
            ]);
            return;
        }
        
        // تایید و ذخیره تاریخ تایید
        $family->verified_at = now();
        $family->verified_by = Auth::id();
        $family->save();
        
        // نمایش پیام موفقیت
        $this->dispatch('show-toast', [
            'message' => 'خانواده با موفقیت تایید شد',
            'type' => 'success'
        ]);
    }
    
    public function copyText($text)
    {
        $this->dispatch('copy-text', $text);
    }
}
