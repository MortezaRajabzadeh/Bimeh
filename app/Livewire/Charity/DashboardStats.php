<?php

namespace App\Livewire\Charity;

use Livewire\Component;
use App\Models\Family;
use App\Models\Member;
use Illuminate\Support\Facades\Auth;

class DashboardStats extends Component
{
    public $insuredFamilies = 0;
    public $uninsuredFamilies = 0;
    public $insuredMembers = 0;
    public $uninsuredMembers = 0;

    public function mount()
    {
        $charityId = Auth::user()->organization_id;
        
        $this->insuredFamilies = Family::where('charity_id', $charityId)
            ->where(function($q) {
                $q->whereHas('insurances')
                  ->orWhere('is_insured', true)
                  ->orWhere('is_insured', 1);
            })
            ->count();
            
        $this->uninsuredFamilies = Family::where('charity_id', $charityId)
            ->whereDoesntHave('insurances')
            ->where(function($q) {
                $q->where('is_insured', false)
                  ->orWhere('is_insured', 0)
                  ->orWhereNull('is_insured');
            })
            ->count();
            
        $this->insuredMembers = Member::whereHas('family', function($q) use ($charityId) {
            $q->where('charity_id', $charityId)
              ->where(function($subQ) {
                  $subQ->whereHas('insurances')
                       ->orWhere('is_insured', true)
                       ->orWhere('is_insured', 1);
              });
        })->count();
        
        $this->uninsuredMembers = Member::whereHas('family', function($q) use ($charityId) {
            $q->where('charity_id', $charityId)
              ->whereDoesntHave('insurances')
              ->where(function($subQ) {
                  $subQ->where('is_insured', false)
                       ->orWhere('is_insured', 0)
                       ->orWhereNull('is_insured');
              });
        })->count();
    }

    public function render()
    {
        return view('livewire.charity.dashboard-stats');
    }
} 