<?php

namespace App\Http\Livewire\Charity;

use App\Models\Family;
use App\Models\Region;
use Livewire\Component;
use Livewire\WithPagination;

class FamilySearch extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $statusFilter = '';
    public $regionFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    // امکان آپدیت پارامترها به صورت URI
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'regionFilter' => ['except' => ''],
        'sortField' => ['except' => 'created_at'],
        'sortDirection' => ['except' => 'desc'],
    ];
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
    
    public function updatingRegionFilter()
    {
        $this->resetPage();
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
    
    public function render()
    {
        $regions = Region::all();
        
        // آمار خانواده‌ها
        $insuredFamilies = Family::where('is_insured', true)->count();
        $uninsuredFamilies = Family::where('is_insured', false)->count();
        
        $query = Family::query()->with(['region', 'members']);
        
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('members', function ($sub) {
                    $sub->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('national_code', 'like', '%' . $this->search . '%');
                })
                ->orWhere('id', 'like', '%' . $this->search . '%')
                ->orWhere('family_code', 'like', '%' . $this->search . '%')
                ->orWhere('address', 'like', '%' . $this->search . '%');
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
            } else {
                $query->orderBy($this->sortField, $this->sortDirection);
            }
        } else {
            $query->latest();
        }
        
        $families = $query->paginate(10);
        
        return view('livewire.charity.family-search', [
            'families' => $families,
            'regions' => $regions,
            'insuredFamilies' => $insuredFamilies,
            'uninsuredFamilies' => $uninsuredFamilies,
        ]);
    }
} 