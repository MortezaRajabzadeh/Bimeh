<?php

namespace App\Http\Livewire\Charity;

use App\Models\Family;
use App\Models\Region;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Schema;

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
            $query->where(function ($q) {
                // جستجو در سرتیترهای مختلف جدول - فقط فیلدهایی که در دیتابیس وجود دارند
                $q->whereHas('members', function ($sub) {
                    $sub->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('national_code', 'like', '%' . $this->search . '%');
                    
                    // اگر ستون موبایل و تاریخ تولد در جدول members وجود دارند، اضافه شوند
                    if (in_array('mobile', Schema::getColumnListing('members'))) {
                        $sub->orWhere('mobile', 'like', '%' . $this->search . '%');
                    }
                    
                    if (in_array('birth_date', Schema::getColumnListing('members'))) {
                        $sub->orWhere('birth_date', 'like', '%' . $this->search . '%');
                    }
                })
                ->orWhere('id', 'like', '%' . $this->search . '%')
                ->orWhere('family_code', 'like', '%' . $this->search . '%')
                ->orWhere('address', 'like', '%' . $this->search . '%');
                
                // اضافه کردن وضعیت بیمه
                if ($this->search === 'بیمه شده' || $this->search === 'بدون بیمه') {
                    $q->orWhere('is_insured', $this->search === 'بیمه شده' ? 1 : 0);
                }
                
                // اضافه کردن جستجو در تاریخ ایجاد
                $q->orWhere('created_at', 'like', '%' . $this->search . '%');
                
                // بررسی وجود ستون‌های دیگر قبل از استفاده
                if (in_array('acceptance_criteria', Schema::getColumnListing('families'))) {
                    $q->orWhere('acceptance_criteria', 'like', '%' . $this->search . '%');
                }
                
                if (in_array('payer', Schema::getColumnListing('families'))) {
                    $q->orWhere('payer', 'like', '%' . $this->search . '%');
                }
                
                if (in_array('participation_percentage', Schema::getColumnListing('families'))) {
                    $q->orWhere('participation_percentage', 'like', '%' . $this->search . '%');
                }
                
                if (in_array('verified_at', Schema::getColumnListing('families'))) {
                    $q->orWhere('verified_at', 'like', '%' . $this->search . '%');
                }
                
                // جستجو در منطقه
                $q->orWhereHas('region', function ($sub) {
                    $sub->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('province', 'like', '%' . $this->search . '%');
                });
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