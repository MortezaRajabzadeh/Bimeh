<?php

namespace App\Livewire\Charity;

use App\Models\Family;
use App\Models\Region;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Province;
use App\Models\City;
use App\Models\RankSetting;
use App\Models\FamilyCriterion;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    public $organizations = [];
    public $selectedHead = null;
    public $perPage = 15;
    public $province = '';
    public $city = '';
    public $deprivation_rank = '';
    public $family_rank_range = '';
    public $specific_criteria = '';
    public $availableRankSettings = [];
    
    // مدیریت فیلترهای پیشرفته
    public $tempFilters = [];
    public $activeFilters = [];
    
    // New ranking properties
    public $showRankModal = false;
    public $rankFilters = [];
    
    protected $paginationTheme = 'tailwind';

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'region' => ['except' => ''],
        'charity' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'family_rank_range' => ['except' => ''],
        'specific_criteria' => ['except' => ''],
        'province' => ['except' => ''],
        'city' => ['except' => ''],
        'deprivation_rank' => ['except' => ''],
        'perPage' => ['except' => 15],
    ];
    
    public function mount()
    {
        $this->regions = Region::all();
        $this->provinces = Province::orderBy('name')->get();
        $this->cities = City::orderBy('name')->get();
        $this->organizations = Organization::where('type', 'charity')->orderBy('name')->get();
        $this->availableRankSettings = RankSetting::active()->ordered()->get();
        
        // مقداردهی اولیه فیلترهای مودالی - حتماً آرایه خالی
        $this->tempFilters = [];
        $this->activeFilters = [];
    }
    
    public function render()
    {
        // ساخت کوئری اصلی
        $query = Family::query()
            ->with([
                'province', 
                'city', 
                'members' => function($q) {
                    $q->orderBy('is_head', 'desc');
                }, 
                'organization',
                'familyCriteria.rankSetting'
            ]);

        // اعمال فیلترها
        $this->applyFiltersToQuery($query);

        // مرتب‌سازی
        $query->orderBy($this->sortField, $this->sortDirection);

        // صفحه‌بندی
        $families = $query->paginate($this->perPage);

        // اگر خانواده‌ای باز شده، اعضای آن را بارگذاری کن
        if ($this->expandedFamily) {
            $this->familyMembers = Member::where('family_id', $this->expandedFamily)
                ->orderBy('is_head', 'desc')
                ->orderBy('created_at')
                ->get();
        }

        // بازگشت view با داده‌ها
        return view('livewire.charity.family-search', [
            'families' => $families,
            'provinces' => $this->provinces,
            'cities' => $this->cities,
            'organizations' => $this->organizations,
            'availableRankSettings' => $this->availableRankSettings,
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
    
    public function updatingFamilyRankRange()
    {
        $this->resetPage();
    }

    public function updatingSpecificCriteria()
    {
        $this->resetPage();
    }
    
    /**
     * دریافت کوئری اصلی خانواده‌ها
     */
    public function getFamiliesQuery()
    {
        return Family::query();
    }

    /**
     * اعمال فیلترها به کوئری
     */
    private function applyFiltersToQuery($query)
    {
        // فیلتر جستجو عمومی
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('family_code', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%')
                  ->orWhere('additional_info', 'like', '%' . $this->search . '%')
                  ->orWhereHas('members', function ($memberQuery) {
                      $memberQuery->where('first_name', 'like', '%' . $this->search . '%')
                                  ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                  ->orWhere('national_code', 'like', '%' . $this->search . '%');
                  });
            });
        }

        // فیلتر وضعیت - اصلاح شده
        if ($this->status) {
            if ($this->status === 'insured') {
                // خانواده‌هایی که is_insured = true یا status = 'insured'
                $query->where(function($q) {
                    $q->where('is_insured', true)
                      ->orWhere('status', 'insured');
                });
            } elseif ($this->status === 'uninsured') {
                // خانواده‌هایی که is_insured = false و status != 'insured'
                $query->where('is_insured', false)
                      ->where('status', '!=', 'insured');
            } else {
                // سایر وضعیت‌ها: pending, reviewing, approved, renewal, rejected, deleted
                $query->where('status', $this->status);
            }
        }

        // فیلتر استان - اصلاح شده
        if ($this->province) {
            $query->where('province_id', $this->province);
        }

        // فیلتر شهر - اصلاح شده  
        if ($this->city) {
            $query->where('city_id', $this->city);
        }

        // فیلتر رتبه محرومیت استان - اصلاح شده
        if ($this->deprivation_rank) {
            $query->whereHas('province', function ($q) {
                switch ($this->deprivation_rank) {
                    case 'high':
                        $q->where('deprivation_rank', '<=', 3);
                        break;
                    case 'medium':
                        $q->whereBetween('deprivation_rank', [4, 6]);
                        break;
                    case 'low':
                        $q->where('deprivation_rank', '>=', 7);
                        break;
                }
            });
        }

        // فیلتر بازه رتبه محرومیت خانواده - نیاز به فیلد calculated_rank
        if ($this->family_rank_range) {
            switch ($this->family_rank_range) {
                case 'very_high': // 80-100
                    $query->where('calculated_rank', '>=', 80)
                          ->where('calculated_rank', '<=', 100);
                    break;
                case 'high': // 60-79
                    $query->where('calculated_rank', '>=', 60)
                          ->where('calculated_rank', '<', 80);
                    break;
                case 'medium': // 40-59
                    $query->where('calculated_rank', '>=', 40)
                          ->where('calculated_rank', '<', 60);
                    break;
                case 'low': // 20-39
                    $query->where('calculated_rank', '>=', 20)
                          ->where('calculated_rank', '<', 40);
                    break;
                case 'very_low': // 0-19
                    $query->where('calculated_rank', '>=', 0)
                          ->where('calculated_rank', '<', 20);
                    break;
            }
        }

        // فیلتر معیار خاص - بهبود یافته برای پشتیبانی از هر دو روش ذخیره‌سازی
        if ($this->specific_criteria) {
            $rankSetting = RankSetting::find($this->specific_criteria);
            if ($rankSetting) {
                $query->where(function($q) use ($rankSetting) {
                    // جستجو در فیلد acceptance_criteria (JSON array)
                    $q->whereJsonContains('acceptance_criteria', $rankSetting->name)
                      // یا جستجو در جدول family_criteria
                      ->orWhereHas('familyCriteria', function ($subQ) use ($rankSetting) {
                          $subQ->where('rank_setting_id', $rankSetting->id)
                               ->where('has_criteria', true);
                      });
                });
            }
        }

        // فیلتر خیریه معرف - اصلاح شده
        if ($this->charity) {
            $query->where('charity_id', $this->charity);
        }

        return $query;
    }

    /**
     * تابع پاک کردن همه فیلترها
     */
    public function clearAllFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->province = '';
        $this->city = '';
        $this->deprivation_rank = '';
        $this->family_rank_range = '';
        $this->specific_criteria = '';
        $this->charity = '';
        $this->resetPage();
    }

    /**
     * بررسی وجود فیلترهای فعال
     */
    public function hasActiveFilters()
    {
        return !empty($this->status) || 
               !empty($this->province) || 
               !empty($this->city) || 
               !empty($this->deprivation_rank) || 
               !empty($this->family_rank_range) || 
               !empty($this->specific_criteria) || 
               !empty($this->charity);
    }

    /**
     * شمارش فیلترهای فعال
     */
    public function getActiveFiltersCount()
    {
        $count = 0;
        if ($this->status) $count++;
        if ($this->province) $count++;
        if ($this->city) $count++;
        if ($this->deprivation_rank) $count++;
        if ($this->family_rank_range) $count++;
        if ($this->specific_criteria) $count++;
        if ($this->charity) $count++;
        return $count;
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
            if ($family->verified_at) {
                $this->dispatch('show-toast', [
                    'message' => '❌ امکان تغییر سرپرست برای خانواده‌های تایید شده وجود ندارد', 
                    'type' => 'error'
                ]);
                return;
            }
            
            // بررسی اینکه عضو انتخاب شده متعلق به همین خانواده است
            $member = Member::where('id', $memberId)->where('family_id', $familyId)->first();
            if (!$member) {
                $this->dispatch('show-toast', [
                    'message' => '❌ عضو انتخاب شده در این خانواده یافت نشد', 
                    'type' => 'error'
                ]);
                return;
            }
            
                // تنظیم متغیر انتخاب شده
                $this->selectedHead = $memberId;
                
                // مدیریت تراکنش برای اطمینان از صحت داده‌ها
                DB::beginTransaction();
                
            // به‌روزرسانی پایگاه داده - فقط یک نفر سرپرست
                Member::where('family_id', $familyId)->update(['is_head' => false]);
                Member::where('id', $memberId)->update(['is_head' => true]);
                
                DB::commit();
                
                // به‌روزرسانی نمایش بدون بارگیری مجدد کامل
                if ($this->expandedFamily === $familyId && !empty($this->familyMembers)) {
                    // به‌روزرسانی state داخلی بدون بارگیری مجدد
                foreach ($this->familyMembers as $familyMember) {
                        // فقط وضعیت is_head را تغییر می‌دهیم
                    $familyMember->is_head = ($familyMember->id == $memberId);
                    }
                }
                
                // نمایش پیام موفقیت
                $this->dispatch('show-toast', [
                'message' => '✅ سرپرست خانواده با موفقیت تغییر یافت', 
                    'type' => 'success'
                ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('show-toast', [
                'message' => '❌ خطا در به‌روزرسانی اطلاعات: ' . $e->getMessage(), 
                'type' => 'error'
            ]);
        }
    }
    
    public function verifyFamily($familyId)
    {
        // بررسی دسترسی کاربر
        if (!Auth::check() || !Gate::allows('verify-family')) {
            $this->dispatch('show-toast', [
                'message' => '🚫 شما اجازه تایید خانواده را ندارید',
                'type' => 'error'
            ]);
            return;
        }
        
        $family = Family::findOrFail($familyId);
        
        // اگر قبلاً تایید شده، اطلاع بدهیم
        if ($family->verified_at) {
            $this->dispatch('show-toast', [
                'message' => '⚠️ این خانواده قبلاً تایید شده است',
                'type' => 'warning'
            ]);
            return;
        }
        
        // بررسی اینکه یک سرپرست انتخاب شده باشد
        $headsCount = Member::where('family_id', $familyId)->where('is_head', true)->count();
        
        if ($headsCount === 0) {
            $this->dispatch('show-toast', [
                'message' => '❌ لطفاً قبل از تایید، یک سرپرست برای خانواده انتخاب کنید',
                'type' => 'error'
            ]);
            return;
        }
        
        if ($headsCount > 1) {
            $this->dispatch('show-toast', [
                'message' => '⚠️ خطا: بیش از یک سرپرست انتخاب شده است. لطفاً فقط یک نفر را انتخاب کنید',
                'type' => 'error'
            ]);
            // اصلاح خودکار - فقط اولین سرپرست را نگه می‌داریم
            $firstHead = Member::where('family_id', $familyId)->where('is_head', true)->first();
            Member::where('family_id', $familyId)->update(['is_head' => false]);
            $firstHead->update(['is_head' => true]);
            return;
        }
        
        // بررسی حداقل یک عضو در خانواده
        $membersCount = Member::where('family_id', $familyId)->count();
        if ($membersCount === 0) {
            $this->dispatch('show-toast', [
                'message' => '❌ این خانواده هیچ عضوی ندارد و قابل تایید نیست',
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
            'message' => '✅ خانواده با موفقیت تایید شد و آماده ارسال به بیمه می‌باشد',
            'type' => 'success'
        ]);
    }
    
    public function copyText($text)
    {
        $this->dispatch('copy-text', $text);
        $this->dispatch('show-toast', [
            'message' => '📋 متن با موفقیت کپی شد: ' . $text,
            'type' => 'success'
        ]);
    }
    
    /**
     * اعمال فیلترهای مودالی
     * 
     * @return void
     */
    public function applyFilters()
    {
        try {
            // Debug: بررسی محتوای tempFilters
            logger('Applying filters - tempFilters:', $this->tempFilters);
            
            // اگر هیچ فیلتری وجود نداره
            if (empty($this->tempFilters)) {
                $this->dispatch('notify', [
                    'message' => 'هیچ فیلتری برای اعمال وجود ندارد',
                    'type' => 'error'
                ]);
                return;
            }
            
            // ابتدا فیلترهای قبلی را پاک می‌کنیم (بدون پاک کردن search)
            $this->status = '';
            $this->province = '';
            $this->city = '';
            $this->deprivation_rank = '';
            $this->charity = '';
            $this->region = '';
            
            $appliedCount = 0;
            
            // اعمال فیلترهای جدید
            foreach ($this->tempFilters as $filter) {
                if (empty($filter['value'])) {
                    logger('Skipping empty filter:', $filter);
                    continue;
                }
                
                logger('Applying filter:', $filter);
                
                switch ($filter['type']) {
                    case 'status':
                        // وضعیت بیمه یا وضعیت عمومی خانواده
                        $this->status = $filter['value'];
                        $appliedCount++;
                        logger('Applied status filter:', ['value' => $filter['value']]);
                        break;
                    case 'province':
                        $this->province = $filter['value'];
                        $appliedCount++;
                        logger('Applied province filter:', ['value' => $filter['value']]);
                        break;
                    case 'city':
                        $this->city = $filter['value'];
                        $appliedCount++;
                        logger('Applied city filter:', ['value' => $filter['value']]);
                        break;
                    case 'deprivation_rank':
                        $this->deprivation_rank = $filter['value'];
                        $appliedCount++;
                        logger('Applied deprivation_rank filter:', ['value' => $filter['value']]);
                        break;
                    case 'charity':
                        $this->charity = $filter['value'];
                        $appliedCount++;
                        logger('Applied charity filter:', ['value' => $filter['value']]);
                        break;
                    case 'members_count':
                        // این فیلتر نیاز به منطق خاص دارد - فعلاً skip می‌کنیم
                        logger('Skipped members_count filter - needs special logic');
                        break;
                    case 'created_at':
                        // این فیلتر نیاز به منطق خاص دارد - فعلاً skip می‌کنیم
                        logger('Skipped created_at filter - needs date range logic');
                        break;
                }
            }
            
            $this->activeFilters = $this->tempFilters;
            $this->resetPage();
            
            // Debug: نمایش وضعیت فعلی فیلترها
            logger('Applied filters result:', [
                'status' => $this->status,
                'province' => $this->province,
                'city' => $this->city,
                'deprivation_rank' => $this->deprivation_rank,
                'charity' => $this->charity,
                'appliedCount' => $appliedCount
            ]);
            
            $this->dispatch('notify', [
                'message' => $appliedCount > 0 ? 
                    "فیلترها با موفقیت اعمال شدند ($appliedCount فیلتر)" : 
                    'هیچ فیلتر معتبری برای اعمال یافت نشد',
                'type' => $appliedCount > 0 ? 'success' : 'error'
            ]);
            
        } catch (\Exception $e) {
            logger('Error applying filters:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->dispatch('notify', [
                'message' => 'خطا در اعمال فیلترها: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
    
    /**
     * بازگشت به تنظیمات پیش‌فرض
     * 
     * @return void
     */
    public function resetToDefault()
    {
        $this->tempFilters = [];
        $this->activeFilters = [];
        $this->clearAllFilters();
        
        $this->dispatch('notify', [
            'message' => 'تنظیمات به حالت پیش‌فرض بازگشت',
            'type' => 'success'
        ]);
    }
    
    /**
     * تست فیلترها - برای debugging
     * 
     * @return void
     */
    public function testFilters()
    {
        // تست محدود کردن نتایج با فیلترهای فعلی
        $filteredQuery = $this->getFamiliesQuery();
        $this->applyFiltersToQuery($filteredQuery);
        
        $count = $filteredQuery->count();
        
        $this->dispatch('notify', [
            'message' => "فیلترهای انتخاب شده {$count} خانواده را نمایش می‌دهد",
            'type' => 'success'
        ]);
    }
    
    // توابع مودال رتبه‌بندی
    public function openRankModal()
    {
        $this->showRankModal = true;
        $this->availableRankSettings = RankSetting::active()->ordered()->get();
        
        // اگر فیلتری وجود ندارد، یک فیلتر پیشفرض اضافه کن
        if (empty($this->rankFilters)) {
            $this->rankFilters = [
                [
                    'type' => 'rank_range',
                    'operator' => 'equals',
                    'value' => '',
                    'label' => ''
                ]
            ];
        }
    }
    
    public function closeRankModal()
    {
        $this->showRankModal = false;
    }
    
    public function clearRankFilters()
    {
        $this->family_rank_range = '';
        $this->specific_criteria = '';
        $this->resetPage();
    }
    
    public function saveRankFilter()
    {
        // ذخیره فیلترها - می‌توانید منطق ذخیره در دیتابیس را اینجا اضافه کنید
        $this->dispatch('notify', [
            'message' => 'فیلترها ذخیره شد',
            'type' => 'success'
        ]);
    }
    
    public function resetRankToDefault()
    {
        $this->rankFilters = [];
        $this->family_rank_range = '';
        $this->specific_criteria = '';
        $this->resetPage();
        
        $this->dispatch('notify', [
            'message' => 'تنظیمات به حالت پیشفرض بازگشت',
            'type' => 'success'
        ]);
    }
    
    public function applyRankFilters()
    {
        try {
            $appliedCount = 0;
            
            // پاک کردن فیلترهای قبلی
            $this->family_rank_range = '';
            $this->specific_criteria = '';
            $this->province = '';
            $this->city = '';
            
            // اعمال فیلترهای جدید
            foreach ($this->rankFilters as $filter) {
                if (empty($filter['value'])) continue;
                
                switch ($filter['type']) {
                    case 'rank_range':
                        $this->family_rank_range = $filter['value'];
                        $appliedCount++;
                        break;
                    case 'criteria':
                        $this->specific_criteria = $filter['value'];
                        $appliedCount++;
                        break;
                    case 'province':
                        $this->province = $filter['value'];
                        $appliedCount++;
                        break;
                    case 'city':
                        $this->city = $filter['value'];
                        $appliedCount++;
                        break;
                }
            }
            
            $this->resetPage();
            
            $this->dispatch('notify', [
                'message' => $appliedCount > 0 ? 
                    "فیلترهای رتبه‌بندی با موفقیت اعمال شدند ($appliedCount فیلتر)" : 
                    'هیچ فیلتر معتبری برای اعمال یافت نشد',
                'type' => $appliedCount > 0 ? 'success' : 'error'
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'message' => 'خطا در اعمال فیلترها: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
}
