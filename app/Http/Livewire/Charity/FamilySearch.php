<?php

namespace App\Http\Livewire\Charity;

use App\Http\Livewire\BaseComponent;
use App\Models\Family;
use App\Models\Member;
use App\Models\Region;
use App\Models\Province;
use App\Models\City;
use App\Models\SavedFilter;
use App\Models\SavedItemPermission;
use App\Models\Organization;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FamilySearch extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    #[Url]
    public $search = '';
    
    #[Url]
    public $status = '';
    
    #[Url]
    public $province = '';
    
    #[Url]
    public $deprivation_rank = '';
    
    #[Url]
    public $city = '';
    
    #[Url]
    public $charity = '';
    
    #[Url]
    public $region = '';
    
    #[Url]
    public $sortField = 'created_at';
    
    #[Url]
    public $sortDirection = 'desc';

    // متغیرهای مورد نیاز برای فیلتر رتبه
    #[Url]
    public $family_rank_range = '';
    
    #[Url]
    public $specific_criteria = '';
    
    // متغیر نمایش مودال تنظیمات رتبه
    public $showRankModal = false;
    
    // متغیرهای مورد نیاز برای تنظیمات رتبه
    public $availableRankSettings = [];
    
    // متغیرهای مورد نیاز برای مدیریت ویرایش و حذف معیارهای رتبه
    public $selectedCriteria = [];
    public $editingRankSetting = null;
    public $isEditingRankSetting = false;
    public $rankSettingName = '';
    public $rankSettingWeight = 0;
    public $rankSettingNeedsDoc = false;
    public $rankSettingColor = 'bg-green-100';
    public $rankSettingDescription = '';
    public $showAddCriteriaForm = false;
    
    // تعداد آیتم‌ها در هر صفحه
    public $perPage = 10;
    
    // ذخیره شناسه خانواده‌ای که توسط کاربر باز شده است
    public $expandedFamily = null;
    
    // اطلاعات اعضای خانواده باز شده
    public $familyMembers = [];
    
    // امکان آپدیت پارامترها به صورت URI
    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'province' => ['except' => ''],
        'deprivation_rank' => ['except' => ''],
        'city' => ['except' => ''],
        'charity' => ['except' => ''],
        'region' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
        'family_rank_range' => ['except' => ''],
        'specific_criteria' => ['except' => ''],
    ];
    
    // این متد برای مطابقت با لایوایر ۳ اضافه می‌شود
    public function updating($name, $value)
    {
        if (in_array($name, ['search', 'status', 'province', 'deprivation_rank', 'city', 'charity'])) {
            $this->resetPage();
        }
    }
    
    public function updated($name, $value)
    {
        if (in_array($name, ['province', 'city', 'charity']) && $value !== '') {
            $this->$name = (int) $value;
        }
        $this->resetPage();
    }
    
    /**
     * باز یا بسته کردن نمایش اعضای خانواده
     *
     * @param int $familyId شناسه خانواده‌
     * @return void
     */
    public function toggleFamily($familyId)
    {
        try {
            if ($this->expandedFamily === $familyId) {
                // اگر روی همان خانواده کلیک شده، آن را ببند
                $this->expandedFamily = null;
                $this->familyMembers = [];
            } else {
                // در غیر این صورت، اطلاعات اعضای خانواده را لود کن
                $this->expandedFamily = $familyId;
                $this->loadFamilyMembers($familyId);
            }
            $this->dispatch('family-toggled', [
                'familyId' => $familyId,
                'isExpanded' => $this->expandedFamily === $familyId,
            ]);
        } catch (\Exception $e) {
            // ثبت خطا
            // ارسال پیام به کاربر
            session()->flash('error', 'خطا در بارگذاری اعضای خانواده. لطفاً دوباره تلاش کنید.');
        }
    }
    
    public function loadFamilyMembers($familyId)
    {
        try {
            // پیدا کردن خانواده و بارگذاری اعضا با معیار مرتب‌سازی
            $family = Family::with(['members' => function($query) {
                // اعضا را مرتب‌سازی می‌کنیم، ابتدا سرپرست و سپس بقیه بر اساس نام
                $query->orderByDesc('is_head')
                      ->orderBy('first_name')
                      ->orderBy('last_name');
            }])->find($familyId);
            
            if ($family) {
                // بارگذاری مستقیم مجموعه به جای استفاده از رابطه کش شده
                $this->familyMembers = $family->members()->orderByDesc('is_head')
                                              ->orderBy('first_name')
                                              ->orderBy('last_name')
                                              ->get();
            } else {
                $this->familyMembers = [];
            }
        } catch (\Exception $e) {
            $this->familyMembers = [];
        }
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
    
    /**
     * نرمال‌سازی متن جستجو برای فارسی
     *
     * @param string $text
     * @return string
     */
    private function normalizeText($text)
    {
        // تبدیل انواع «ی» به «ی» استاندارد
        $text = str_replace(['ي', 'ى', 'ئ'], 'ی', $text);
        
        // تبدیل «ک» عربی به «ک» فارسی
        $text = str_replace('ك', 'ک', $text);
        
        // تبدیل اعداد فارسی به انگلیسی برای جستجوهای عددی
        $persianNums = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishNums = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $text = str_replace($persianNums, $englishNums, $text);
        
        return $text;
    }
    
    #[Computed]
    public function regions()
    {
        return Region::all();
    }
    
    #[Computed]
    public function provinces()
    {
        return Province::all();
    }
    
    #[Computed]
    public function cities()
    {
        return City::all();
    }
    
    #[Computed]
    public function families()
    {
        // جستجو در خانواده‌ها
        $query = Family::query()
            ->select('families.*')
            ->with([
                'city',
                'province',
                'organization',
                'members',
                'region',
                'rankSettings'
            ])
            ->withCount('members');
        
        // ایجاد کوئری برای جستجو در اعضای خانواده
        $searchQuery = function($q) {
            // نرمال‌سازی متن جستجو برای فارسی
            $normalizedSearch = $this->normalizeText($this->search);
            
            $q->where(function($subQ) use ($normalizedSearch) {
                $searchTerms = explode(' ', $normalizedSearch);
                
                foreach ($searchTerms as $term) {
                    if (strlen($term) < 2) continue;
                    
                    $subQ->orWhere('members.first_name', 'LIKE', "%{$term}%")
                          ->orWhere('members.last_name', 'LIKE', "%{$term}%")
                          ->orWhere('members.national_code', 'LIKE', "%{$term}%")
                          ->orWhere('members.mobile', 'LIKE', "%{$term}%")
                          ->orWhere('members.birth_certificate_number', 'LIKE', "%{$term}%");
                }
            });
        };
        
        // اعمال جستجو بر اساس نام خانواده، شماره، کد ملی، یا موبایل اعضا
        if ($this->search && strlen($this->search) >= 2) {
            $normalizedSearch = $this->normalizeText($this->search);
            
            $query->where(function($q) use ($normalizedSearch, $searchQuery) {
                $searchTerms = explode(' ', $normalizedSearch);
                
                foreach ($searchTerms as $term) {
                    if (strlen($term) < 2) continue;
                    
                    $q->orWhere('families.code', 'LIKE', "%{$term}%")
                      ->orWhere('families.name', 'LIKE', "%{$term}%");
                }
                
                // جستجو در اعضای خانواده
                $q->orWhereHas('members', $searchQuery);
            });
        }
        
        // فیلتر بر اساس وضعیت
        if ($this->status) {
            if ($this->status == 'verified') {
                $query->whereNotNull('verified_at');
            } elseif ($this->status == 'unverified') {
                $query->whereNull('verified_at');
            } elseif ($this->status == 'insured') {
                $query->whereHas('familyInsurance', function($q) {
                    $q->whereNotNull('insured_at');
                });
            } elseif ($this->status == 'uninsured') {
                $query->whereDoesntHave('familyInsurance', function($q) {
                    $q->whereNotNull('insured_at');
                });
            }
        }
        
        // فیلتر بر اساس استان
        if ($this->province) {
            $query->where('province_id', $this->province);
        }
        
        // فیلتر بر اساس رتبه محرومیت
        if ($this->deprivation_rank) {
            $query->whereHas('province', function($q) {
                $q->where('deprivation_rank', $this->deprivation_rank);
            });
        }
        
        // فیلتر بر اساس محدوده رتبه خانواده
        if ($this->family_rank_range) {
            $rangeParts = explode('-', $this->family_rank_range);
            if (count($rangeParts) == 2) {
                $minRank = (int)$rangeParts[0];
                $maxRank = (int)$rangeParts[1];
                $query->whereBetween('family_rank', [$minRank, $maxRank]);
            }
        }
        
        // فیلتر بر اساس معیارهای خاص
        if ($this->specific_criteria) {
            $criteria = explode(',', $this->specific_criteria);
            $query->whereHas('rankSettings', function($q) use ($criteria) {
                $q->whereIn('key', $criteria);
            });
        }

        // فیلتر بر اساس شهر
        if ($this->city) {
            $query->where('city_id', $this->city);
        }
        
        // فیلتر بر اساس منطقه
        if ($this->region) {
            $query->where('region_id', $this->region);
        }
        
        // فیلتر بر اساس خیریه
        if ($this->charity) {
            $query->where('organization_id', $this->charity);
        }
        
        // نمایش فقط خانواده‌های قابل دسترسی برای کاربر غیر ادمین
        if (!auth()->user()->hasRole('admin')) {
            $query->where(function($q) {
                // خانواده‌های مربوط به خیریه کاربر
                $q->where('organization_id', auth()->user()->organization_id);
                
                // یا خانواده‌های بدون خیریه
                $q->orWhereNull('organization_id');
            });
        }
        
        // مرتب‌سازی
        if ($this->sortField === 'province.name') {
            $query->join('provinces', 'families.province_id', '=', 'provinces.id')
                  ->orderBy('provinces.name', $this->sortDirection);
        } elseif ($this->sortField === 'city.name') {
            $query->join('cities', 'families.city_id', '=', 'cities.id')
                  ->orderBy('cities.name', $this->sortDirection);
        } elseif ($this->sortField === 'organization.name') {
            $query->leftJoin('organizations', 'families.organization_id', '=', 'organizations.id')
                  ->orderBy('organizations.name', $this->sortDirection);
        } else {
            // اعمال مرتب‌سازی بر اساس معیارهای رتبه‌بندی
            if ($this->sortField === 'calculated_score') {
                $query->orderByRaw('(
                    SELECT SUM(rs.weight * fs.value) as score 
                    FROM rank_settings as rs 
                    JOIN family_rank_settings as fs ON rs.id = fs.rank_setting_id 
                    WHERE fs.family_id = families.id 
                    GROUP BY fs.family_id
                ) ' . $this->sortDirection);
            } else {
                $query->orderBy($this->sortField, $this->sortDirection);
            }
        }
        
        return $query->paginate($this->perPage);
    }
    
    #[Computed]
    public function insuredFamilies()
    {
        return Family::where('is_insured', true)->count();
    }
    
    #[Computed]
    public function uninsuredFamilies()
    {
        return Family::where('is_insured', false)->count();
    }
    
    /**
     * تغییر سرپرست خانواده
     *
     * @param int $familyId شناسه خانواده
     * @param int $memberId شناسه عضو جدید به عنوان سرپرست
     * @return void
     */
    public function setFamilyHead($familyId, $memberId)
    {
        try {
            DB::beginTransaction();
            
            // پیدا کردن خانواده
            $family = Family::findOrFail($familyId);
            
            // لغو سرپرستی از همه اعضای فعلی خانواده
            $family->members()->update(['is_head' => false]);
            
            // تعیین عضو جدید به عنوان سرپرست
            $member = Member::findOrFail($memberId);
            $member->is_head = true;
            $member->save();
            
            DB::commit();
            
            // بارگذاری مجدد لیست اعضا به طور مستقیم از پایگاه داده
            $this->familyMembers = Member::where('family_id', $familyId)
                                   ->orderByDesc('is_head')
                                   ->orderBy('first_name')
                                   ->orderBy('last_name')
                                   ->get();
            
            // نمایش پیام موفقیت
            $this->dispatch('show-toast', [
                'message' => 'سرپرست خانواده با موفقیت تغییر کرد',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            // ثبت خطا
            
            // نمایش پیام خطا
            $this->dispatch('show-toast', [
                'message' => 'خطا در تغییر سرپرست خانواده: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
    
    /**
     * تایید خانواده
     *
     * @param int $familyId شناسه خانواده
     * @return void
     */
    public function verifyFamily($familyId)
    {
        try {
            // پیدا کردن خانواده
            $family = Family::findOrFail($familyId);
            
            // بررسی اینکه آیا عضو سرپرست تعیین شده است
            $hasHead = $family->members()->where('is_head', true)->exists();
            
            if (!$hasHead) {
                $this->dispatch('show-toast', [
                    'message' => 'لطفا ابتدا سرپرست خانواده را تعیین کنید',
                    'type' => 'error'
                ]);
                return;
            }
            
            // تایید خانواده
            $family->verified_at = now();
            $family->save();
            
            // نمایش پیام موفقیت
            $this->dispatch('show-toast', [
                'message' => 'خانواده با موفقیت تایید شد',
                'type' => 'success'
            ]);
        } catch (\Exception $e) {
            // ثبت خطا
            
            // نمایش پیام خطا
            $this->dispatch('show-toast', [
                'message' => 'خطا در تایید خانواده: ' . $e->getMessage(),
                'type' => 'error'
            ]);
        }
    }
    
    public function copyText($text)
    {
        $this->dispatch('copy-text', text: $text);
        $this->dispatch('show-toast', [
            'title' => 'کپی شد!',
            'message' => 'متن مورد نظر با موفقیت کپی شد.',
            'type' => 'success'
        ]);
    }
    
    /**
     * باز کردن مودال تنظیمات رتبه
     * 
     * @return void
     */
    public function openRankModal()
    {
        $this->availableRankSettings = \App\Models\RankSetting::orderBy('sort_order')->get();
        
        // Initialize selectedCriteria from specific_criteria if set
        if ($this->specific_criteria) {
            $this->selectedCriteria = explode(',', $this->specific_criteria);
        } else {
            $this->selectedCriteria = [];
        }
        
        $this->showRankModal = true;
        $this->dispatch('show-rank-modal');
    }
    
    /**
     * بستن مودال تنظیمات رتبه
     * 
     * @return void
     */
    public function closeRankModal()
    {
        $this->showRankModal = false;
    }
    
    /**
     * اعمال فیلتر رتبه با محدوده مشخص
     * 
     * @param string $range محدوده رتبه (مثال: "1-3")
     * @return void
     */
    public function applyRankRange($range)
    {
        $this->family_rank_range = $range;
        $this->showRankModal = false;
        $this->resetPage();
    }
    
    /**
     * اعمال فیلتر معیارهای خاص
     * 
     * @param array $criteria لیست معیارها
     * @return void
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
        cache()->forget('families_query_' . auth()->id());
    }
    
    /**
     * ویرایش تنظیمات رتبه
     * 
     * @param int $id شناسه تنظیمات رتبه
     * @return void
     */
    public function editRankSetting($id)
    {
        $this->editingRankSetting = \App\Models\RankSetting::find($id);
        if ($this->editingRankSetting) {
            $this->rankSettingName = $this->editingRankSetting->name;
            $this->rankSettingWeight = $this->editingRankSetting->weight;
            $this->rankSettingNeedsDoc = $this->editingRankSetting->needs_doc;
            $this->rankSettingColor = $this->editingRankSetting->bg_color;
            $this->rankSettingDescription = $this->editingRankSetting->description;
            $this->isEditingRankSetting = true;
        }
    }
    
    /**
     * ذخیره تنظیمات رتبه
     * 
     * @return void
     */
    public function saveRankSetting()
    {
        // Validate
        $this->validate([
            'rankSettingName' => 'required|string|max:255',
            'rankSettingWeight' => 'required|numeric|min:0|max:10',
        ]);
        
        if ($this->isEditingRankSetting && $this->editingRankSetting) {
            // Update existing
            $this->editingRankSetting->update([
                'name' => $this->rankSettingName,
                'weight' => $this->rankSettingWeight,
                'needs_doc' => $this->rankSettingNeedsDoc,
                'bg_color' => $this->rankSettingColor,
                'description' => $this->rankSettingDescription,
            ]);
        } else {
            // Create new
            \App\Models\RankSetting::create([
                'name' => $this->rankSettingName,
                'weight' => $this->rankSettingWeight,
                'needs_doc' => $this->rankSettingNeedsDoc,
                'bg_color' => $this->rankSettingColor,
                'description' => $this->rankSettingDescription,
                'sort_order' => \App\Models\RankSetting::max('sort_order') + 1,
            ]);
        }
        
        // Reset form and refresh available settings
        $this->resetRankSettingForm();
        $this->availableRankSettings = \App\Models\RankSetting::orderBy('sort_order')->get();
    }
    
    /**
     * حذف تنظیمات رتبه
     * 
     * @param int $id شناسه تنظیمات رتبه
     * @return void
     */
    public function deleteRankSetting($id)
    {
        $setting = \App\Models\RankSetting::find($id);
        if ($setting) {
            $setting->delete();
            $this->availableRankSettings = \App\Models\RankSetting::orderBy('sort_order')->get();
        }
    }
    
    /**
     * بازنشانی فرم تنظیمات رتبه
     * 
     * @return void
     */
    public function resetRankSettingForm()
    {
        $this->editingRankSetting = null;
        $this->isEditingRankSetting = false;
        $this->rankSettingName = '';
        $this->rankSettingWeight = 0;
        $this->rankSettingNeedsDoc = false;
        $this->rankSettingColor = 'bg-green-100';
        $this->rankSettingDescription = '';
    }
    
    /**
     * بازگرداندن تنظیمات به حالت پیشفرض
     * 
     * @return void
     */
    public function resetToDefaults()
    {
        // پاک کردن فیلترهای رتبه
        $this->family_rank_range = null;
        $this->specific_criteria = null;
        $this->selectedCriteria = [];
        
        // بازنشانی فرم و بستن مودال
        $this->resetRankSettingForm();
        $this->closeRankModal();
        
        // بازنشانی صفحه‌بندی و به‌روزرسانی لیست
        $this->resetPage();
        
        // پاک کردن کش برای اطمینان از به‌روزرسانی داده‌ها
        cache()->forget('families_query_' . auth()->id());
    }
    
    /**
     * Reset all filters to their default values
     *
     * @return bool
     */
    public function resetFilters()
    {
        try {
            // Reset all filter variables
            $this->search = '';
            $this->status = '';
            $this->province = '';
            $this->deprivation_rank = '';
            $this->city = '';
            $this->charity = '';
            $this->region = '';
            $this->family_rank_range = '';
            $this->specific_criteria = '';
            $this->selectedCriteria = [];
            
            // Reset sorting to default
            $this->sortField = 'created_at';
            $this->sortDirection = 'desc';
            
            // Reset pagination
            $this->resetPage();
            
            // Clear cache
            cache()->forget('families_query_' . auth()->id());
            
            // Dispatch event for UI updates
            $this->dispatch('filters-reset');
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error resetting filters: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'message' => 'خطا در بازنشانی فیلترها: ' . $e->getMessage(),
                'type' => 'error'
            ]);
            return false;
        }
    }
    
    /**
     * ذخیره فیلتر فعلی
     *
     * @param string $name نام فیلتر
     * @param string $description توضیحات فیلتر
     * @param string $visibility سطح دسترسی فیلتر
     * @return bool
     */
    public function saveFilter($name, $description = '', $visibility = 'private')
    {
        try {
            // اعتبارسنجی ورودی
            if (empty(trim($name))) {
                $this->dispatch('show-toast', [
                    'message' => 'نام فیلتر الزامی است',
                    'type' => 'error'
                ]);
                return false;
            }
            
            // تهیه پیکربندی فیلتر فعلی
            $filtersConfig = [
                'search' => $this->search,
                'status' => $this->status,
                'province' => $this->province,
                'deprivation_rank' => $this->deprivation_rank,
                'city' => $this->city,
                'charity' => $this->charity,
                'region' => $this->region,
                'family_rank_range' => $this->family_rank_range,
                'specific_criteria' => $this->specific_criteria,
                'sortField' => $this->sortField,
                'sortDirection' => $this->sortDirection,
            ];
            
            // بررسی اینکه فیلتری با همین نام برای این کاربر وجود ندارد
            $existingFilter = SavedFilter::where('user_id', auth()->id())
                                        ->where('name', trim($name))
                                        ->first();
            
            if ($existingFilter) {
                $this->dispatch('show-toast', [
                    'message' => 'فیلتری با این نام قبلاً ذخیره شده است',
                    'type' => 'error'
                ]);
                return false;
            }
            
            // ایجاد فیلتر جدید
            SavedFilter::create([
                'name' => trim($name),
                'description' => trim($description),
                'user_id' => auth()->id(),
                'organization_id' => auth()->user()->organization_id,
                'filters_config' => $filtersConfig,
                'visibility' => $visibility,
                'is_active' => true,
                'usage_count' => 0,
                'last_used_at' => now(),
            ]);
            
            $this->dispatch('show-toast', [
                'message' => 'فیلتر با موفقیت ذخیره شد',
                'type' => 'success'
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error saving filter: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'message' => 'خطا در ذخیره فیلتر: ' . $e->getMessage(),
                'type' => 'error'
            ]);
            return false;
        }
    }
    
    /**
     * بارگذاری فیلترهای ذخیره شده قابل دسترسی برای کاربر
     *
     * @return array
     */
    public function loadSavedFilters()
    {
        try {
            $filters = SavedFilter::visible(auth()->user())
                ->with(['user', 'organization'])
                ->orderBy('last_used_at', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($filter) {
                    return [
                        'id' => $filter->id,
                        'name' => $filter->name,
                        'description' => $filter->description,
                        'visibility' => $filter->visibility,
                        'usage_count' => $filter->usage_count,
                        'created_at' => $filter->created_at->format('Y/m/d'),
                        'last_used_at' => $filter->last_used_at ? $filter->last_used_at->format('Y/m/d H:i') : 'هرگز',
                        'user_name' => $filter->user->name ?? 'نامشخص',
                        'is_owner' => $filter->user_id === auth()->id(),
                        'can_edit' => $filter->canEdit(auth()->user()),
                        'can_delete' => $filter->canDelete(auth()->user()),
                    ];
                })
                ->toArray();
            
            return $filters;
        } catch (\Exception $e) {
            \Log::error('Error loading saved filters: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * بارگذاری فیلتر ذخیره شده و اعمال آن
     *
     * @param int $filterId شناسه فیلتر
     * @return bool
     */
    public function loadFilter($filterId)
    {
        try {
            $filter = SavedFilter::visible(auth()->user())->find($filterId);
            
            if (!$filter) {
                $this->dispatch('show-toast', [
                    'message' => 'فیلتر مورد نظر یافت نشد یا دسترسی به آن ندارید',
                    'type' => 'error'
                ]);
                return false;
            }
            
            // اعمال تنظیمات فیلتر
            $config = $filter->filters_config;
            
            $this->search = $config['search'] ?? '';
            $this->status = $config['status'] ?? '';
            $this->province = $config['province'] ?? '';
            $this->deprivation_rank = $config['deprivation_rank'] ?? '';
            $this->city = $config['city'] ?? '';
            $this->charity = $config['charity'] ?? '';
            $this->region = $config['region'] ?? '';
            $this->family_rank_range = $config['family_rank_range'] ?? '';
            $this->specific_criteria = $config['specific_criteria'] ?? '';
            $this->sortField = $config['sortField'] ?? 'created_at';
            $this->sortDirection = $config['sortDirection'] ?? 'desc';
            
            // به‌روزرسانی selectedCriteria اگر specific_criteria تنظیم شده باشد
            if ($this->specific_criteria) {
                $this->selectedCriteria = explode(',', $this->specific_criteria);
            } else {
                $this->selectedCriteria = [];
            }
            
            // بازنشانی صفحه‌بندی
            $this->resetPage();
            
            // افزایش تعداد استفاده و به‌روزرسانی آخرین زمان استفاده
            $filter->incrementUsage();
            
            // پاک کردن کش
            cache()->forget('families_query_' . auth()->id());
            
            $this->dispatch('show-toast', [
                'message' => 'فیلتر "' . $filter->name . '" با موفقیت بارگذاری شد',
                'type' => 'success'
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error loading filter: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'message' => 'خطا در بارگذاری فیلتر: ' . $e->getMessage(),
                'type' => 'error'
            ]);
            return false;
        }
    }
    
    /**
     * حذف فیلتر ذخیره شده
     *
     * @param int $filterId شناسه فیلتر
     * @return bool
     */
    public function deleteFilter($filterId)
    {
        try {
            $filter = SavedFilter::find($filterId);
            
            if (!$filter) {
                $this->dispatch('show-toast', [
                    'message' => 'فیلتر مورد نظر یافت نشد',
                    'type' => 'error'
                ]);
                return false;
            }
            
            if (!$filter->canDelete(auth()->user())) {
                $this->dispatch('show-toast', [
                    'message' => 'شما مجوز حذف این فیلتر را ندارید',
                    'type' => 'error'
                ]);
                return false;
            }
            
            $filterName = $filter->name;
            $filter->delete();
            
            $this->dispatch('show-toast', [
                'message' => 'فیلتر "' . $filterName . '" با موفقیت حذف شد',
                'type' => 'success'
            ]);
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error deleting filter: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'message' => 'خطا در حذف فیلتر: ' . $e->getMessage(),
                'type' => 'error'
            ]);
            return false;
        }
    }
    
    // Livewire listeners
    protected $listeners = [
        'refresh-family-list' => '$refresh',
        'updateProvince' => 'updateProvince',
        'openRankModal' => 'openRankModal',
        'closeRankModal' => 'closeRankModal',
        'applyCriteria' => 'applyCriteria',
        'resetToDefaults' => 'resetToDefaults',
        'editRankSetting' => 'editRankSetting',
        'deleteRankSetting' => 'deleteRankSetting',
        'saveRankSetting' => 'saveRankSetting',
        'resetRankSettingForm' => 'resetRankSettingForm'
    ];
    
    public function render()
    {
        // آمار خانواده‌ها و اعضا برای نمایش در بالای صفحه
        $insuredFamilies = $this->insuredFamilies();
        $uninsuredFamilies = $this->uninsuredFamilies();
        $insuredMembers = Member::where('has_insurance', true)->count();
        $uninsuredMembers = Member::where('has_insurance', false)->count();

        return view('livewire.charity.family-search', [
            'families' => $this->families(),
            'regions' => $this->regions(),
            'provinces' => $this->provinces(),
            'cities' => $this->cities(),
            'insuredFamilies' => $insuredFamilies,
            'uninsuredFamilies' => $uninsuredFamilies,
            'insuredMembers' => $insuredMembers,
            'uninsuredMembers' => $uninsuredMembers,
        ]);
    }
} 
