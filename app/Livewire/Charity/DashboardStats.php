<?php

namespace App\Livewire\Charity;

use Livewire\Component;
use App\Models\Family;
use App\Models\Member;

class DashboardStats extends Component
{
    public $insuredFamilies = 0;
    public $uninsuredFamilies = 0;
    public $insuredMembers = 0;
    public $uninsuredMembers = 0;

    public function mount()
    {
        $this->insuredFamilies = Family::where('is_insured', true)->count();
        $this->uninsuredFamilies = Family::where('is_insured', false)->count();
        $this->insuredMembers = Member::whereHas('family', fn($q) => $q->where('is_insured', true))->count();
        $this->uninsuredMembers = Member::whereHas('family', fn($q) => $q->where('is_insured', false))->count();
    }

    public function render()
    {
        return view('livewire.charity.dashboard-stats');
    }
} 