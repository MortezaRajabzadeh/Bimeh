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
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

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
    
    // Properties for new Rank Settings Modal
    public $rankSettings = [];
    public $editingRankSettingId = null;
    public $editingRankSetting = [
        'name' => '',
        'weight' => 5,
        'description' => '',
        'requires_document' => true,
        'color' => '#60A5FA'
    ];
    public $isCreatingNew = false;
    
    // اضافه کردن پراپرتی‌های مورد نیاز
    public $rankingSchemes = [];
    public $availableCriteria = [];
    
    // پراپرتی‌های جدید سیستم رتبه‌بندی پویا
    public $selectedSchemeId = null;
    public array $schemeWeights = [];
    public $newSchemeName = '';
    public $newSchemeDescription = '';
    public $appliedSchemeId = null;
    
    // مدیریت فیلترهای پیشرفته
    public $tempFilters = [];
    public $activeFilters = [];
    
    // New ranking properties
    public $showRankModal = false;
    public $rankFilters = [];
    
    // اضافه کردن متغیرهای فرم معیار جدید
    public $rankSettingName = '';
    public $rankSettingDescription = '';
    public $rankSettingWeight = 5;
    public $rankSettingColor = 'bg-green-100';
    public $rankSettingNeedsDoc = 1;
    
    // متغیرهای مورد نیاز برای مودال رتبه‌بندی جدید
    public $selectedCriteria = [];
    public $criteriaRequireDocument = [];
    
    protected $paginationTheme = 'tailwind';

    // Define Livewire event listeners to enable frontend component interactions
    protected $listeners = [
        'openRankModal',
        'closeRankModal',
        'applyCriteria',
        'editRankSetting',
        'saveRankSetting',
        'deleteRankSetting',
        'resetToDefaults',
        'applyAndClose',
        'copyText',
    ];

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
        
        // بارگذاری معیارهای رتبه‌بندی در ابتدای لود صفحه
        $this->availableRankSettings = RankSetting::active()->ordered()->get();
        
        // مقداردهی اولیه متغیرهای رتبه‌بندی
        $this->rankingSchemes = \App\Models\RankingScheme::orderBy('name')->get();
        $this->availableCriteria = \App\Models\RankSetting::where('is_active', true)->orderBy('sort_order')->get();
        
        // مقداردهی اولیه فیلترهای مودالی - حتماً آرایه خالی
        $this->tempFilters = [];
        $this->activeFilters = [];
        
        // تست ارسال نوتیفیکیشن
        $this->dispatch('notify', [
            'message' => 'صفحه جستجوی خانواده‌ها با موفقیت بارگذاری شد',
            'type' => 'success'
        ]);
    }
    
    public function render() 
    { 
        $query = Family::query() 
            ->with([ 
                'province', 
                'city', 
                'members' => fn($q) => $q->orderBy('is_head', 'desc'), 
                'organization', 
                'familyCriteria.rankSetting' 
            ]); 
    
        $this->applyFiltersToQuery($query); 
    
        // Dynamic Ranking Logic 
        if ($this->appliedSchemeId) { 
            $schemeCriteria = \App\Models\RankingSchemeCriterion::where('ranking_scheme_id', $this->appliedSchemeId) 
                ->pluck('weight', 'rank_setting_id'); 
            
            if ($schemeCriteria->isNotEmpty()) { 
                $cases = []; 
                foreach ($schemeCriteria as $rank_setting_id => $weight) { 
                    // Assumption: A 'family_criteria' pivot table exists. 
                    $cases[] = "CASE WHEN EXISTS (SELECT 1 FROM family_criteria fc WHERE fc.family_id = families.id AND fc.rank_setting_id = {$rank_setting_id} AND fc.has_criteria = true) THEN {$weight} ELSE 0 END"; 
                } 
                
                if (!empty($cases)) { 
                    $selectRaw = 'families.*, (' . implode(' + ', $cases) . ') as calculated_score'; 
                    $query->selectRaw($selectRaw); 
                } 
            } 
        } 
    
        // Sorting Logic 
        if ($this->sortField === 'calculated_score' && $this->appliedSchemeId) { 
            $query->orderBy('calculated_score', $this->sortDirection); 
        } elseif ($this->sortField) { 
            $query->orderBy($this->sortField, $this->sortDirection); 
        } 
    
        $families = $query->paginate($this->perPage); 
        
        // نمایش تعداد خانواده‌های فیلتر شده (فقط موقع تغییر فیلترها)
        if ($this->hasActiveFilters() && request()->has(['status', 'province', 'city', 'deprivation_rank', 'family_rank_range', 'specific_criteria', 'charity', 'region'])) {
            $totalCount = $families->total();
            $activeFiltersCount = $this->getActiveFiltersCount();
            $this->dispatch('notify', [
                'message' => "نمایش {$totalCount} خانواده براساس {$activeFiltersCount} فیلتر فعال",
                'type' => 'info'
            ]);
        }
    
        if ($this->expandedFamily) { 
            $this->familyMembers = Member::where('family_id', $this->expandedFamily) 
                ->orderBy('is_head', 'desc') 
                ->orderBy('created_at') 
                ->get(); 
        } 
    
        return view('livewire.charity.family-search', [ 
            'families' => $families, 
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
            $appliedFilters = [];
            
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
                        $appliedFilters[] = 'وضعیت: ' . $filter['value'];
                        logger('Applied status filter:', ['value' => $filter['value']]);
                        break;
                    case 'province':
                        $this->province = $filter['value'];
                        $appliedCount++;
                        $provinceName = Province::find($filter['value'])->name ?? $filter['value'];
                        $appliedFilters[] = 'استان: ' . $provinceName;
                        logger('Applied province filter:', ['value' => $filter['value']]);
                        break;
                    case 'city':
                        $this->city = $filter['value'];
                        $appliedCount++;
                        $cityName = City::find($filter['value'])->name ?? $filter['value'];
                        $appliedFilters[] = 'شهر: ' . $cityName;
                        logger('Applied city filter:', ['value' => $filter['value']]);
                        break;
                    case 'deprivation_rank':
                        $this->deprivation_rank = $filter['value'];
                        $appliedCount++;
                        $appliedFilters[] = 'رتبه محرومیت: ' . $filter['value'];
                        logger('Applied deprivation_rank filter:', ['value' => $filter['value']]);
                        break;
                    case 'charity':
                        $this->charity = $filter['value'];
                        $appliedCount++;
                        $charityName = Organization::find($filter['value'])->name ?? $filter['value'];
                        $appliedFilters[] = 'موسسه: ' . $charityName;
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
            
            // پیام با جزئیات فیلترهای اعمال شده
            if ($appliedCount > 0) {
                $filtersList = implode('، ', $appliedFilters);
                $message = "فیلترها با موفقیت اعمال شدند: {$filtersList}";
            } else {
                $message = 'هیچ فیلتر معتبری برای اعمال یافت نشد';
            }
            
            $this->dispatch('notify', [
                'message' => $message,
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
     * بازگشت به تنظیمات پیشفرض
     */
    public function resetToDefaultSettings()
    {
        // پاک کردن معیارهای انتخاب شده
        $this->selectedCriteria = [];
        $this->criteriaRequireDocument = [];
        
        // مقداردهی مجدد با مقادیر پیشفرض
        foreach ($this->availableCriteria as $criterion) {
            $this->selectedCriteria[$criterion->id] = false;
            $this->criteriaRequireDocument[$criterion->id] = true;
        }
        
        $this->dispatch('notify', ['message' => 'تنظیمات به حالت پیشفرض بازگشت.', 'type' => 'info']);
    }
    
    //====================================================================== 
    //== متدهای سیستم رتبه‌بندی پویا 
    //====================================================================== 
    
    /** 
     * وزن‌های یک الگوی رتبه‌بندی ذخیره‌شده را بارگیری می‌کند. 
     */ 
    
    public function loadScheme($schemeId) 
    { 
        if (empty($schemeId)) { 
            $this->reset(['selectedSchemeId', 'schemeWeights', 'newSchemeName', 'newSchemeDescription']); 
            return; 
        } 
    
        $this->selectedSchemeId = $schemeId; 
        $scheme = \App\Models\RankingScheme::with('criteria')->find($schemeId); 
        
        if ($scheme) { 
            $this->newSchemeName = $scheme->name; 
            $this->newSchemeDescription = $scheme->description; 
            $this->schemeWeights = $scheme->criteria->pluck('pivot.weight', 'id')->toArray(); 
        } 
    } 
    
    /** 
     * یک الگوی رتبه‌بندی جدید را ذخیره یا یک الگوی موجود را به‌روزرسانی می‌کند. 
     */ 
    public function saveScheme() 
    { 
        $this->validate([ 
            'newSchemeName' => 'required|string|max:255', 
            'newSchemeDescription' => 'nullable|string', 
            'schemeWeights' => 'required|array', 
            'schemeWeights.*' => 'nullable|integer|min:0' 
        ]); 
    
        $scheme = \App\Models\RankingScheme::updateOrCreate( 
            ['id' => $this->selectedSchemeId], 
            [ 
                'name' => $this->newSchemeName, 
                'description' => $this->newSchemeDescription, 
                'user_id' => \Illuminate\Support\Facades\Auth::id() 
            ] 
        ); 
        
        $weightsToSync = []; 
        foreach ($this->schemeWeights as $criterionId => $weight) { 
            if (!is_null($weight) && $weight > 0) { 
                $weightsToSync[$criterionId] = ['weight' => $weight]; 
            } 
        } 
        
        $scheme->criteria()->sync($weightsToSync); 
        
        $this->rankingSchemes = \App\Models\RankingScheme::orderBy('name')->get(); 
        $this->selectedSchemeId = $scheme->id; 
    
        $this->dispatch('notify', ['message' => 'الگو با موفقیت ذخیره شد.', 'type' => 'success']); 
    } 
    
    /** 
     * الگوی انتخاب‌شده را برای فیلتر کردن و مرتب‌سازی اعمال می‌کند. 
     */ 
    public function applyRankingScheme() 
    { 
        if (!$this->selectedSchemeId) { 
             $this->dispatch('notify', ['message' => 'لطفا ابتدا یک الگو را انتخاب یا ذخیره کنید.', 'type' => 'error']); 
             return; 
        } 
        $this->appliedSchemeId = $this->selectedSchemeId; 
        $this->sortBy('calculated_score'); 
        $this->resetPage(); 
        $this->showRankModal = false; 
        
        // دریافت نام الگوی انتخاب شده برای نمایش در پیام
        $schemeName = \App\Models\RankingScheme::find($this->selectedSchemeId)->name ?? '';
        $this->dispatch('notify', [
            'message' => "الگوی رتبه‌بندی «{$schemeName}» با موفقیت اعمال شد.",
            'type' => 'success'
        ]); 
    } 
    
    /** 
     * رتبه‌بندی اعمال‌شده را پاک می‌کند. 
     */ 
    public function clearRanking() 
    { 
        $this->appliedSchemeId = null; 
        $this->sortBy('created_at'); 
        $this->resetPage(); 
        $this->showRankModal = false; 
        $this->dispatch('notify', ['message' => 'فیلتر رتبه‌بندی حذف شد.', 'type' => 'info']); 
    }
    public function applyAndClose() 
    { 
        try {
            // اطمینان از ذخیره همه تغییرات
            $this->loadRankSettings();
            
            // بروزرسانی لیست معیارهای در دسترس
            $this->availableRankSettings = \App\Models\RankSetting::active()->ordered()->get();
            
            // اعمال تغییرات به خانواده‌ها
            if ($this->appliedSchemeId) {
                // اگر یک طرح رتبه‌بندی انتخاب شده باشد، دوباره آن را اعمال می‌کنیم
                $this->applyRankingScheme();

                $this->sortBy('calculated_score');
            }
            
            // بستن مودال و نمایش پیام
            $this->showRankModal = false;
            $this->dispatch('notify', [
                'message' => 'تغییرات با موفقیت اعمال شد.',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // خطا در اعمال تغییرات
            $this->dispatch('notify', [
                'message' => 'خطا در اعمال تغییرات: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
    
    public function loadRankSettings()
    {
        // استفاده از آبجکت کالکشن بدون تبدیل به آرایه
        $this->rankSettings = RankSetting::orderBy('sort_order')->get();
        
        // نمایش پیام مناسب برای باز شدن تنظیمات
        $this->dispatch('notify', [
            'message' => 'تنظیمات معیارهای رتبه‌بندی بارگذاری شد - ' . count($this->rankSettings) . ' معیار',
            'type' => 'info'
        ]);
    }
    
    /**
     * فرم افزودن معیار جدید را نمایش می‌دهد.
     */
    public function showCreateForm()
    {
        $this->reset('editingRankSettingId');
        $this->isCreatingNew = true;
        $this->editingRankSetting = [
            'name' => '',
            'weight' => 5,
            'description' => '',
            'requires_document' => true,
            'color' => '#'.substr(str_shuffle('ABCDEF0123456789'), 0, 6)
        ];
        
        $this->dispatch('notify', [
            'message' => 'فرم ایجاد معیار جدید آماده شد',
            'type' => 'info'
        ]);
    }
    
    /**
     * باز کردن مودال تنظیمات رتبه
     */
    public function openRankModal()
    {
        // بارگذاری مجدد معیارهای رتبه‌بندی با اسکوپ active و ordered
        // با لود کردن به صورت collection (بدون ->toArray())
        $this->availableRankSettings = RankSetting::active()->ordered()->get();
        
        // ثبت در لاگ برای اشکال‌زدایی - با استفاده از متد count() کالکشن
        Log::info('مودال رتبه باز شد', [
            'loaded_criteria_count' => count($this->availableRankSettings)
        ]);
        
        // مقداردهی اولیه فیلدهای فرم معیار جدید
        $this->resetRankSettingForm();
        
        // Initialize selectedCriteria from specific_criteria if set
        if ($this->specific_criteria) {
            $this->selectedCriteria = explode(',', $this->specific_criteria);
        } else {
            $this->selectedCriteria = [];
        }
        
        $this->showRankModal = true;
        $this->dispatch('show-rank-modal');
        
        // نمایش پیام برای کاربر - با استفاده از متد count() کالکشن
        $this->dispatch('notify', [
            'message' => count($this->availableRankSettings) . ' معیار رتبه‌بندی بارگذاری شد',
            'type' => 'info'
        ]);
    }
    
    /**
     * بستن مودال تنظیمات رتبه
     */
    public function closeRankModal()
    {
        $this->showRankModal = false;
    }
    
    /**
     * اعمال معیارهای انتخاب شده
     */
    public function applyCriteria()
    {
        if (!empty($this->selectedCriteria)) {
            $this->specific_criteria = implode(',', $this->selectedCriteria);
        } else {
            $this->specific_criteria = null;
        }
        
        $this->resetPage();
        $this->closeRankModal();
        
        // Clear cache to ensure fresh data
        if (Auth::check()) {
            cache()->forget('families_query_' . Auth::id());
        }
        
        $this->dispatch('notify', [
            'message' => 'معیارهای انتخاب‌شده با موفقیت اعمال شدند',
            'type' => 'success'
        ]);
    }
    
    /**
     * ویرایش تنظیمات رتبه
     */
    public function editRankSetting($id)
    {
        $setting = RankSetting::find($id);
        if ($setting) {
            // پر کردن فرم با مقادیر معیار موجود
            $this->rankSettingName = $setting->name;
            $this->rankSettingDescription = $setting->description;
            $this->rankSettingWeight = $setting->weight;
            $this->rankSettingColor = $setting->sort_order ?? 'bg-green-100';
            $this->rankSettingNeedsDoc = $setting->requires_document ? 1 : 0;
            $this->editingRankSettingId = $id;
            $this->isCreatingNew = false;
            
            $this->dispatch('notify', [
                'message' => 'در حال ویرایش معیار: ' . $setting->name,
                'type' => 'info'
            ]);
        }
    }
    
    /**
     * ذخیره تنظیمات رتبه (جدید یا ویرایش شده)
     */
    public function saveRankSetting()
    {
        // ثبت لاگ برای اشکال‌زدایی قبل از شروع فرآیند
        Log::info('درخواست ذخیره معیار رتبه', [
            'data' => [
                'name' => $this->rankSettingName,
                'description' => $this->rankSettingDescription,
                'weight' => $this->rankSettingWeight,
                'requires_document' => $this->rankSettingNeedsDoc,
                'color' => $this->rankSettingColor,
                'is_editing' => !empty($this->editingRankSettingId),
                'editing_id' => $this->editingRankSettingId
            ]
        ]);

        // ابتدا اعتبارسنجی مقادیر ورودی
        if (empty($this->rankSettingName)) {
            $this->dispatch('notify', [
                'message' => 'نام معیار الزامی است',
                'type' => 'error'
            ]);
            return;
        }
        
        try {
            // تعیین آیا در حال ایجاد معیار جدید هستیم یا ویرایش معیار موجود
            if (empty($this->editingRankSettingId)) {
                // ایجاد معیار جدید با استفاده از مدل
$setting = new RankSetting();
$setting->fill([
    'name' => $this->rankSettingName,
    'weight' => (int)$this->rankSettingWeight,
    'description' => $this->rankSettingDescription,
    'requires_document' => (bool)$this->rankSettingNeedsDoc,
    'color' => $this->rankSettingColor, // Fixed: color field instead of sort_order
    'sort_order' => RankSetting::max('sort_order') + 10,
    'is_active' => true,
    'slug' => Str::slug($this->rankSettingName) ?: 'rank-' . Str::random(6),
    'created_by' => \Illuminate\Support\Facades\Auth::id() // Track who created it using facade
]);

// Save with error handling
try {
    $setting->save();
} catch (\Exception $e) {
    throw new \Exception('Failed to save rank setting: ' . $e->getMessage());
}
                
                Log::info('معیار جدید ایجاد شد', [
                    'id' => $setting->id,
                    'name' => $setting->name
                ]);
                
                $this->dispatch('notify', [
                    'message' => 'معیار جدید با موفقیت ایجاد شد: ' . $this->rankSettingName,
                    'type' => 'success'
                ]);
            } else {
                // ویرایش معیار موجود
                $setting = RankSetting::find($this->editingRankSettingId);
                if ($setting) {
                    $setting->name = $this->rankSettingName;
                    $setting->weight = $this->rankSettingWeight;
                    $setting->description = $this->rankSettingDescription;
                    $setting->requires_document = (bool)$this->rankSettingNeedsDoc;
                    $setting->sort_order = $this->rankSettingColor;
                    $setting->save();
                    
                    Log::info('معیار ویرایش شد', [
                        'id' => $setting->id,
                        'name' => $setting->name
                    ]);
                    
                    $this->dispatch('notify', [
                        'message' => 'معیار با موفقیت به‌روزرسانی شد: ' . $this->rankSettingName,
                        'type' => 'success'
                    ]);
                }
            }
            
            // بارگذاری مجدد تنظیمات و ریست فرم
            $this->availableRankSettings = RankSetting::active()->ordered()->get();
            $this->resetRankSettingForm();
            
            // ریست کردن فرم بعد از ذخیره موفق
            $this->rankSettingName = '';
            $this->rankSettingDescription = '';
            $this->rankSettingWeight = 5;
            $this->rankSettingColor = 'bg-green-100';
            $this->rankSettingNeedsDoc = 1;
            $this->editingRankSettingId = null;
        } catch (\Exception $e) {
            // ثبت خطا در لاگ
            Log::error('خطا در ذخیره معیار', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('notify', [
                'message' => 'خطا در ذخیره معیار: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
    
    /**
     * ریست کردن فرم معیار
     */
    private function resetRankSettingForm()
    {
        $this->rankSettingName = '';
        $this->rankSettingDescription = '';
        $this->rankSettingWeight = 5;
        $this->rankSettingColor = 'bg-green-100';
        $this->rankSettingNeedsDoc = 1;
        $this->editingRankSettingId = null;
    }
    
    /**
     * بازگشت به تنظیمات پیشفرض
     */
    public function resetToDefaults()
    {
        // پاک کردن فیلترهای رتبه
        $this->family_rank_range = null;
        $this->specific_criteria = null;
        $this->selectedCriteria = [];
        
        // بازنشانی صفحه‌بندی و به‌روزرسانی لیست
        $this->resetPage();
        $this->closeRankModal();
        
        // پاک کردن کش برای اطمینان از به‌روزرسانی داده‌ها
        if (Auth::check()) {
            cache()->forget('families_query_' . Auth::id());
        }
        
        $this->dispatch('notify', [
            'message' => 'تنظیمات رتبه با موفقیت به حالت پیشفرض بازگردانده شد',
            'type' => 'success'
        ]);
    }

    /**
     * حذف معیار
     */
    public function deleteRankSetting($id)
    {
        try {
            $setting = RankSetting::find($id);
            if ($setting) {
                $name = $setting->name;
                $setting->delete();
                
                $this->dispatch('notify', [
                    'message' => "معیار «{$name}» با موفقیت حذف شد",
                    'type' => 'warning'
                ]);
                
                // بارگذاری مجدد لیست
                $this->availableRankSettings = RankSetting::active()->ordered()->get();
            }
        } catch (\Exception $e) {
            Log::error('خطا در حذف معیار', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('notify', [
                'message' => 'خطا در حذف معیار: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
}
