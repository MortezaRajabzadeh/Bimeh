<?php

namespace App\Livewire\Charity;

use App\Models\Family;
use App\Models\Region;
use Livewire\Component;
use Livewire\WithPagination;

class FamilySearch extends Component
{
    use WithPagination;
    
    public $search = '';
    public $status = '';
    public $region = '';
    public $regions = [];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'region' => ['except' => ''],
    ];
    
    public function mount()
    {
        $this->regions = Region::all();
    }
    
    public function render()
    {
        $query = Family::query()
            ->with('region');
        
        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('phone', 'like', '%' . $this->search . '%')
                  ->orWhere('address', 'like', '%' . $this->search . '%');
            });
        }
        
        if ($this->status !== '') {
            $query->where('is_insured', $this->status);
        }
        
        if ($this->region) {
            $query->where('region_id', $this->region);
        }
        
        $families = $query->paginate(10);
        
        return view('livewire.charity.family-search', [
            'families' => $families,
            'regions' => $this->regions
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
}
