<?php

namespace App\Http\Livewire\Charity;

use App\Models\Family;
use App\Models\Region;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
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
            $searchTerm = $this->search;
            $query->where(function ($q) use ($searchTerm) {
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
                
                // 3. جستجو در فیلدهای اصلی خانواده
                $q->orWhere('id', 'like', '%' . $searchTerm . '%')
                  ->orWhere('family_code', 'like', '%' . $searchTerm . '%')
                  ->orWhere('address', 'like', '%' . $searchTerm . '%');
                
                // 4. جستجو بر اساس تعداد اعضا
                if (is_numeric($searchTerm)) {
                    $familiesWithMemberCount = DB::table('families')
                        ->join('members', 'families.id', '=', 'members.family_id')
                        ->select('families.id', DB::raw('count(*) as member_count'))
                        ->groupBy('families.id')
                        ->having('member_count', '=', (int)$searchTerm)
                        ->pluck('families.id')
                        ->toArray();
                    
                    if (!empty($familiesWithMemberCount)) {
                        $q->orWhereIn('id', $familiesWithMemberCount);
                    }
                }
                
                // 5. جستجو بر اساس وضعیت بیمه
                if (in_array(mb_strtolower($searchTerm, 'UTF-8'), ['بیمه شده', 'بیمه', 'بیمه دارد'])) {
                    $q->orWhere('is_insured', true);
                } elseif (in_array(mb_strtolower($searchTerm, 'UTF-8'), ['بدون بیمه', 'بیمه ندارد'])) {
                    $q->orWhere('is_insured', false);
                }
                
                // 6. جستجو در تاریخ ایجاد
                $q->orWhere('created_at', 'like', '%' . $searchTerm . '%');
                
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
                if ($searchTerm == '۵۰٪' || $searchTerm == '50%' || $searchTerm == '50') {
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
                $query->whereHas('members', function($q) {
                    $q->where('is_head', true)
                      ->orderBy('first_name', $this->sortDirection)
                      ->orderBy('last_name', $this->sortDirection);
                });
            } elseif ($this->sortField === 'members_count') {
                $query->withCount('members')
                      ->orderBy('members_count', $this->sortDirection);
            } elseif ($this->sortField === 'consumption_coefficient' || $this->sortField === 'payer' || $this->sortField === 'participation_percentage' || $this->sortField === 'verified_at') {
                // این ستون‌ها ممکن است در دیتابیس وجود نداشته باشند
                $query->latest('id');
            } else {
                $query->orderBy($this->sortField, $this->sortDirection);
            }
        } else {
            $query->latest();
        }
        
        return $query->paginate(10);
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
    
    public function render()
    {
        return view('livewire.charity.family-search', [
            'regions' => $this->regions(),
            'families' => $this->families(),
            'insuredFamilies' => $this->insuredFamilies(),
            'uninsuredFamilies' => $this->uninsuredFamilies(),
        ]);
    }
} 