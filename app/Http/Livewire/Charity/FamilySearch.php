<?php

namespace App\Http\Livewire\Charity;

use App\Http\Livewire\BaseComponent;
use App\Models\Family;
use App\Models\Member;
use App\Models\Region;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FamilySearch extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    #[Url]
    public $search = '';
    
    #[Url]
    public $statusFilter = '';
    
    #[Url]
    public $regionFilter = '';
    
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
        'statusFilter' => ['except' => ''],
        'regionFilter' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];
    
    // این متد برای مطابقت با لایوایر ۳ اضافه می‌شود
    public function updating($name, $value)
    {
        if (in_array($name, ['search', 'statusFilter', 'regionFilter'])) {
            $this->resetPage();
        }
    }
    
    /**
     * باز یا بسته کردن نمایش اعضای خانواده
     *
     * @param int $familyId شناسه خانواده‌
     * @return void
     */
    public function toggleFamily($familyId)
    {
        if ($this->expandedFamily === $familyId) {
            // اگر روی همان خانواده کلیک شده، آن را ببند
            $this->expandedFamily = null;
            $this->familyMembers = [];
        } else {
            // در غیر این صورت، اطلاعات اعضای خانواده را لود کن
            $this->expandedFamily = $familyId;
            $this->loadFamilyMembers($familyId);
        }
    }
    
    public function loadFamilyMembers($familyId)
    {
        $family = Family::with(['members' => function($query) {
            // اعضا را مرتب‌سازی می‌کنیم، ابتدا سرپرست و سپس بقیه بر اساس نام
            $query->orderByDesc('is_head')
                  ->orderBy('first_name')
                  ->orderBy('last_name');
        }])->find($familyId);
        
        if ($family) {
            $this->familyMembers = $family->members;
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
    public function families()
    {
        $query = Family::query()->with(['region', 'members']);
        
        if ($this->search) {
            $originalSearchTerm = $this->search;
            $searchTerm = $this->normalizeText($this->search);
            
            $query->where(function ($q) use ($searchTerm, $originalSearchTerm) {
                // جستجو در تمام سرتیترهای جدول
                
                // 1. جستجو در اطلاعات اعضای خانواده
                $q->whereHas('members', function ($sub) use ($searchTerm) {
                    $sub->where('first_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('national_code', 'like', '%' . $searchTerm . '%');
                    
                    // جستجو در فیلدهای اضافی اعضا در صورت وجود
                    if (in_array('mobile', Schema::getColumnListing('members'))) {
                        $sub->orWhere('mobile', 'like', '%' . $searchTerm . '%');
                    }
                    
                    if (in_array('birth_date', Schema::getColumnListing('members'))) {
                        $sub->orWhere('birth_date', 'like', '%' . $searchTerm . '%');
                    }
                });
                
                // 2. جستجوی خاص برای سرپرست خانوار
                $q->orWhereHas('members', function ($sub) use ($searchTerm) {
                    $sub->where('is_head', true)
                        ->where(function ($s) use ($searchTerm) {
                            $s->where('first_name', 'like', '%' . $searchTerm . '%')
                              ->orWhere('last_name', 'like', '%' . $searchTerm . '%')
                              ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', '%' . $searchTerm . '%');
                        });
                });
                
                // جستجو با کلمه کلیدی "سرپرست"
                if (in_array(mb_strtolower($searchTerm, 'UTF-8'), ['سرپرست', 'سرپرست خانوار', 'سرپرستان'])) {
                    $q->orWhereHas('members', function($s) {
                        $s->where('is_head', true);
                    });
                }
                
                // 3. جستجو در فیلدهای اصلی خانواده
                $q->orWhere('id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('family_code', 'like', '%' . $searchTerm . '%')
                  ->orWhere('address', 'like', '%' . $searchTerm . '%');
                
                // 4. جستجو بر اساس تعداد اعضا
                if (is_numeric($searchTerm)) {
                    $memberCount = (int)$searchTerm;
                    
                    // روش بهینه‌تر برای پیدا کردن خانواده‌ها با تعداد اعضای مشخص
                    $familiesWithMemberCount = DB::table('families')
                        ->join('members', function($join) {
                            $join->on('families.id', '=', 'members.family_id')
                                 ->whereNull('members.deleted_at'); // در نظر گرفتن soft delete
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
                
                // 5. جستجو بر اساس وضعیت بیمه
                $insuredKeywords = ['بیمه شده', 'بیمه', 'بیمه دارد', 'دارای بیمه', 'با بیمه', 'تحت پوشش بیمه'];
                $uninsuredKeywords = ['بدون بیمه', 'بیمه ندارد', 'فاقد بیمه', 'بیمه نشده'];
                
                if (in_array(mb_strtolower($searchTerm, 'UTF-8'), $insuredKeywords)) {
                    $q->orWhere('is_insured', true);
                } elseif (in_array(mb_strtolower($searchTerm, 'UTF-8'), $uninsuredKeywords)) {
                    $q->orWhere('is_insured', false);
                }
                
                // 6. جستجو در تاریخ ایجاد
                $q->orWhere('created_at', 'like', '%' . $searchTerm . '%');
                
                // جستجو در تاریخ تقویم شمسی 
                // فرض می‌کنیم کاربر ممکن است با فرمت‌های مختلف 1402/01/01 یا 1402-01-01 جستجو کند
                $datePattern = '/^[0-9۰-۹]{2,4}[\/\-][0-9۰-۹]{1,2}[\/\-][0-9۰-۹]{1,2}$/';
                if (preg_match($datePattern, $originalSearchTerm) || preg_match($datePattern, $searchTerm)) {
                    // اینجا جستجوی تاریخ شمسی انجام می‌شود
                    // در حالت واقعی نیاز به تبدیل تاریخ شمسی به میلادی و سپس جستجو در created_at داریم
                    $q->orWhere('created_at', 'like', '%' . str_replace(['/', '-'], ['-', '-'], $searchTerm) . '%');
                }
                
                // 7. بررسی وجود ستون‌های دیگر قبل از جستجو
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
                
                // 8. جستجو در اطلاعات منطقه
                $q->orWhereHas('region', function ($sub) use ($searchTerm) {
                    $sub->where('name', 'like', '%' . $searchTerm . '%')
                        ->orWhere('province', 'like', '%' . $searchTerm . '%');
                });
                
                // 9. جستجو برای ضریبه مصرف (اگر در دیتابیس نیست، می‌توانیم در مقادیر هاردکد شده جستجو کنیم)
                if (in_array($searchTerm, ['۵۰٪', '50%', '50', '۵۰', 'ضریبه مصرف', 'ضریب مصرف'])) {
                    // این یک بررسی خاص است که چون ضریبه مصرف در دیتابیس نیست و ثابت است
                    $q->orWhere('id', '>', 0); // این شرط همیشه برقرار است و همه رکوردها را برمی‌گرداند
                }
            });
        }
        
        if ($this->statusFilter === 'insured') {
            $query->where('is_insured', true);
        } elseif ($this->statusFilter === 'uninsured') {
            $query->where('is_insured', false);
        }
        
        if ($this->regionFilter) {
            $query->where('region_id', $this->regionFilter);
        }
        
        // اعمال مرتب‌سازی
        if ($this->sortField) {
            // مرتب‌سازی های خاص
            if ($this->sortField === 'province') {
                $query->join('regions', 'families.region_id', '=', 'regions.id')
                      ->orderBy('regions.province', $this->sortDirection)
                      ->select('families.*');
            } elseif ($this->sortField === 'city') {
                $query->join('regions', 'families.region_id', '=', 'regions.id')
                      ->orderBy('regions.name', $this->sortDirection)
                      ->select('families.*');
            } elseif ($this->sortField === 'head_name') {
                // بهبود مرتب‌سازی بر اساس نام سرپرست
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
                // مرتب‌سازی بر اساس ایدی خانوار، چون ضریبه مصرف فعلاً یکسان است
                $query->orderBy('id', $this->sortDirection);
            } elseif ($this->sortField === 'payer') {
                // اگر ستون payer وجود دارد، بر اساس آن مرتب می‌کنیم، در غیر این صورت بر اساس تاریخ ایجاد
                if (in_array('payer', Schema::getColumnListing('families'))) {
                    $query->orderBy('payer', $this->sortDirection);
                } else {
                    $query->orderBy('created_at', $this->sortDirection);
                }
            } elseif ($this->sortField === 'participation_percentage') {
                // مشابه بالا
                if (in_array('participation_percentage', Schema::getColumnListing('families'))) {
                    $query->orderBy('participation_percentage', $this->sortDirection);
                } else {
                    $query->orderBy('created_at', $this->sortDirection);
                }
            } elseif ($this->sortField === 'verified_at') {
                // مشابه بالا
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
            'insuredFamilies' => $insuredFamilies,
            'uninsuredFamilies' => $uninsuredFamilies,
            'insuredMembers' => $insuredMembers,
            'uninsuredMembers' => $uninsuredMembers,
        ]);
    }
} 