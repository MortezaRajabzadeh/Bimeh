<?php

namespace App\Http\Livewire\Charity;

use App\Http\Livewire\BaseComponent;
use App\Models\Family;
use App\Models\Member;
use App\Models\Region;
use App\Models\Province;
use App\Models\City;
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
            $this->dispatchBrowserEvent('family-toggled', ['familyId' => $familyId, 'isExpanded' => $this->expandedFamily === $familyId]);
        } catch (\Exception $e) {
            // ثبت خطا
            \Illuminate\Support\Facades\Log::error('خطا در نمایش اعضای خانواده: ' . $e->getMessage());
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
                \Illuminate\Support\Facades\Log::warning('خانواده با شناسه ' . $familyId . ' یافت نشد.');
            }
        } catch (\Exception $e) {
            $this->familyMembers = [];
            \Illuminate\Support\Facades\Log::error('خطا در بارگذاری اعضای خانواده: ' . $e->getMessage());
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
        $query = Family::query()->with(['members']);
        
        if ($this->search) {
            $originalSearchTerm = $this->search;
            $searchTerm = $this->normalizeText($this->search);
            
            $query->where(function ($q) use ($searchTerm, $originalSearchTerm) {
                // جستجو در اعضا
                $q->whereHas('members', function ($sub) use ($searchTerm) {
                    $sub->where('first_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('national_code', 'like', '%' . $searchTerm . '%');
                    if (in_array('mobile', Schema::getColumnListing('members'))) {
                        $sub->orWhere('mobile', 'like', '%' . $searchTerm . '%');
                    }
                    if (in_array('birth_date', Schema::getColumnListing('members'))) {
                        $sub->orWhere('birth_date', 'like', '%' . $searchTerm . '%');
                    }
                });
                // جستجوی خاص سرپرست
                $q->orWhereHas('members', function ($sub) use ($searchTerm) {
                    $sub->where('is_head', true)
                        ->where(function ($s) use ($searchTerm) {
                            $s->where('first_name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                              ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%' . $searchTerm . '%');
                        });
                });
                if (in_array(mb_strtolower($searchTerm, 'UTF-8'), ['سرپرست', 'سرپرست خانوار', 'سرپرستان'])) {
                    $q->orWhereHas('members', function($s) {
                        $s->where('is_head', true);
                    });
                }
                // جستجو در فیلدهای خانواده
                $q->orWhere('id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('family_code', 'like', '%' . $searchTerm . '%')
                  ->orWhere('address', 'like', '%' . $searchTerm . '%')
                  ->orWhere('region', 'like', '%' . $searchTerm . '%'); // فقط فیلد متنی
                // جستجو بر اساس تعداد اعضا
                if (is_numeric($searchTerm)) {
                    $memberCount = (int)$searchTerm;
                    $familiesWithMemberCount = DB::table('families')
                        ->join('members', function($join) {
                            $join->on('families.id', '=', 'members.family_id')
                                 ->whereNull('members.deleted_at');
                        })
                        ->select('families.id', DB::raw('count(*) as member_count'))
                        ->whereNull('families.deleted_at')
                        ->groupBy('families.id')
                        ->having('member_count', '=', $memberCount)
                        ->pluck('families.id')
                        ->toArray();
                    if (!empty($familiesWithMemberCount)) {
                        $q->orWhereIn('id', $familiesWithMemberCount);
                    }
                }
                // جستجو بر اساس وضعیت بیمه
                $insuredKeywords = ['بیمه شده', 'بیمه', 'بیمه دارد', 'دارای بیمه', 'با بیمه', 'تحت پوشش بیمه'];
                $uninsuredKeywords = ['بدون بیمه', 'بیمه ندارد', 'فاقد بیمه', 'بیمه نشده'];
                if (in_array(mb_strtolower($searchTerm, 'UTF-8'), $insuredKeywords)) {
                    $q->orWhere('is_insured', true);
                } elseif (in_array(mb_strtolower($searchTerm, 'UTF-8'), $uninsuredKeywords)) {
                    $q->orWhere('is_insured', false);
                }
                $q->orWhere('created_at', 'like', '%' . $searchTerm . '%');
                $datePattern = '/^[0-9۰-۹]{2,4}[\/\-][0-9۰-۹]{1,2}[\/\-][0-9۰-۹]{1,2}$/';
                if (preg_match($datePattern, $originalSearchTerm) || preg_match($datePattern, $searchTerm)) {
                    $q->orWhere('created_at', 'like', '%' . str_replace(['/', '-'], ['-', '-'], $searchTerm) . '%');
                }
                if (in_array('acceptance_criteria', Schema::getColumnListing('families'))) {
                    $q->orWhere('acceptance_criteria', 'like', '%' . $searchTerm . '%');
                }
                if (in_array('payer', Schema::getColumnListing('families'))) {
                    $q->orWhere('payer', 'like', '%' . $searchTerm . '%');
                }
                if (in_array('participation_percentage', Schema::getColumnListing('families'))) {
                    $q->orWhere('participation_percentage', 'like', '%' . $searchTerm . '%');
                }
                if (in_array('verified_at', Schema::getColumnListing('families'))) {
                    $q->orWhere('verified_at', 'like', '%' . $searchTerm . '%');
                }
                // جستجو برای درصد مشارکت
                if (in_array($searchTerm, ['۵۰٪', '50%', '50', '۵۰', 'درصد مشارکت', 'ضریب مشارکت'])) {
                    $q->orWhere('id', '>', 0);
                }
            });
        }
        if ($this->status) {
            if ($this->status === 'insured') {
                $query->where('status', 'insured');
            } elseif ($this->status === 'uninsured') {
                $query->where('status', 'uninsured');
            }
        }
        if ($this->province) {
            $query->where('province_id', $this->province);
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
        if ($this->city) {
            $query->where('city_id', $this->city);
        }
        if ($this->charity) {
            $query->where('charity_id', $this->charity);
        }
        if ($this->region) {
            $query->where('region_id', $this->region);
        }
        // مرتب‌سازی
        if ($this->sortField) {
            if ($this->sortField === 'province') {
                $query->orderBy('province_id', $this->sortDirection);
            } elseif ($this->sortField === 'city') {
                $query->orderBy('city_id', $this->sortDirection);
            } elseif ($this->sortField === 'region_deprivation_rank') {
                $query->leftJoin('regions', 'families.region_id', '=', 'regions.id')
                      ->orderBy('regions.deprivation_rank', $this->sortDirection)
                      ->select('families.*');
            } elseif ($this->sortField === 'province_deprivation_rank') {
                $query->leftJoin('provinces', 'families.province_id', '=', 'provinces.id')
                      ->orderBy('provinces.deprivation_rank', $this->sortDirection)
                      ->select('families.*');
            } elseif ($this->sortField === 'head_name') {
                $query->leftJoin('members', function($join) {
                    $join->on('families.id', '=', 'members.family_id')
                         ->where('members.is_head', true)
                         ->whereNull('members.deleted_at');
                })
                ->orderBy('members.first_name', $this->sortDirection)
                ->orderBy('members.last_name', $this->sortDirection)
                ->select('families.*');
            } elseif ($this->sortField === 'members_count') {
                $query->withCount('members')
                      ->orderBy('members_count', $this->sortDirection);
            } elseif ($this->sortField === 'consumption_coefficient') {
                $query->orderBy('id', $this->sortDirection);
            } elseif ($this->sortField === 'payer') {
                if (in_array('payer', Schema::getColumnListing('families'))) {
                    $query->orderBy('payer', $this->sortDirection);
                } else {
                    $query->orderBy('created_at', $this->sortDirection);
                }
            } elseif ($this->sortField === 'participation_percentage') {
                if (in_array('participation_percentage', Schema::getColumnListing('families'))) {
                    $query->orderBy('participation_percentage', $this->sortDirection);
                } else {
                    $query->orderBy('created_at', $this->sortDirection);
                }
            } elseif ($this->sortField === 'verified_at') {
                if (in_array('verified_at', Schema::getColumnListing('families'))) {
                    $query->orderBy('verified_at', $this->sortDirection);
                } else {
                    $query->orderBy('created_at', $this->sortDirection);
                }
            } else {
                $query->orderBy($this->sortField, $this->sortDirection);
            }
        } else {
            $query->latest();
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
            \Illuminate\Support\Facades\Log::error('خطا در تغییر سرپرست خانواده: ' . $e->getMessage());
            
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
            \Illuminate\Support\Facades\Log::error('خطا در تایید خانواده: ' . $e->getMessage());
            
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