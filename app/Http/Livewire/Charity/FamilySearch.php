<?php

namespace App\Http\Livewire\Charity;

use App\Models\Family;
use App\Models\Region;
use App\Models\Member;
use Livewire\Component;
use Livewire\WithPagination;

class FamilySearch extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public $search = '';
    public $statusFilter = '';
    public $regionFilter = '';
    
    // امکان آپدیت پارامترها به صورت URI
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'regionFilter' => ['except' => ''],
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
    
    public function render()
    {
        $regions = Region::all();
        
        // آمار خانواده‌ها و اعضا
        $insuredFamilies = Family::where('is_insured', true)->count();
        $uninsuredFamilies = Family::where('is_insured', false)->count();
        $insuredMembers = Member::whereHas('family', function($q) {
            $q->where('is_insured', true);
        })->count();
        $uninsuredMembers = Member::whereHas('family', function($q) {
            $q->where('is_insured', false);
        })->count();
        
        $query = Family::query()->with(['region', 'members']);
        
        if ($this->search) {
            $query->where(function ($q) {
                $q->whereHas('members', function ($sub) {
                    $sub->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('national_code', 'like', '%' . $this->search . '%');
                })
                ->orWhere('id', 'like', '%' . $this->search . '%')
                ->orWhere('custom_id', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->statusFilter === '1') {
            $query->where('is_insured', true);
        } elseif ($this->statusFilter === '0') {
            $query->where('is_insured', false);
        }
        
        if ($this->regionFilter) {
            $query->where('region_id', $this->regionFilter);
        }
        
        $families = $query->paginate(10);
        
        return view('livewire.charity.family-search', [
            'families' => $families,
            'regions' => $regions,
            'insuredFamilies' => $insuredFamilies,
            'uninsuredFamilies' => $uninsuredFamilies,
            'insuredMembers' => $insuredMembers,
            'uninsuredMembers' => $uninsuredMembers,
        ]);
    }
} 