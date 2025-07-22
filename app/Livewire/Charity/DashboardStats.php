<?php

namespace App\Livewire\Charity;

use Livewire\Component;
use App\Models\Family;
use App\Models\Member;
use App\Models\Province;
use App\Models\FamilyCriterion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardStats extends Component
{
    public $insuredFamilies = 0;
    public $uninsuredFamilies = 0;
    public $insuredMembers = 0;
    public $uninsuredMembers = 0;
    public $maleCount = 0;
    public $femaleCount = 0;
    public $provinceNames = [];
    public $provinceMaleCounts = [];
    public $provinceFemaleCounts = [];
    public $provinceDeprivedCounts = [];
    public $criteriaData = [];

    public function mount()
    {
        $charityId = Auth::user()->organization_id;
        
        // آمار اصلی
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

        // آمار جنسیتی
        $this->maleCount = Member::whereHas('family', function($q) use ($charityId) {
            $q->where('charity_id', $charityId);
        })->where('gender', 'male')->count();

        $this->femaleCount = Member::whereHas('family', function($q) use ($charityId) {
            $q->where('charity_id', $charityId);
        })->where('gender', 'female')->count();

        // آمار جغرافیایی
        $this->loadGeographicData($charityId);

        // آمار معیارها
        $this->loadCriteriaData($charityId);
    }

    private function loadGeographicData($charityId)
    {
        // دریافت آمار به تفکیک استان
        $provinceStats = DB::table('members')
            ->join('families', 'members.family_id', '=', 'families.id')
            ->join('cities', 'families.city_id', '=', 'cities.id')
            ->join('provinces', 'cities.province_id', '=', 'provinces.id')
            ->where('families.charity_id', $charityId)
            ->select(
                'provinces.name as province_name',
                DB::raw('COUNT(CASE WHEN members.gender = "male" THEN 1 END) as male_count'),
                DB::raw('COUNT(CASE WHEN members.gender = "female" THEN 1 END) as female_count'),
                // موقتاً از تعداد کل خانواده‌ها به عنوان محروم در نظر می‌گیریم
                DB::raw('COUNT(DISTINCT families.id) as deprived_count')
            )
            ->groupBy('provinces.id', 'provinces.name')
            ->orderBy('provinces.name')
            ->get();

        $this->provinceNames = $provinceStats->pluck('province_name')->toArray();
        $this->provinceMaleCounts = $provinceStats->pluck('male_count')->toArray();
        $this->provinceFemaleCounts = $provinceStats->pluck('female_count')->toArray();
        $this->provinceDeprivedCounts = $provinceStats->pluck('deprived_count')->toArray();
    }

    private function loadCriteriaData($charityId)
    {
        // آمار معیارهای پذیرش
        $totalFamilies = Family::where('charity_id', $charityId)->count();
        $totalMembers = Member::whereHas('family', function($q) use ($charityId) {
            $q->where('charity_id', $charityId);
        })->count();

        // موقتاً تمام خانواده‌ها را محروم در نظر می‌گیریم تا خطا رفع شود
        $deprivedFamiliesCount = $totalFamilies;

        // محاسبه خانواده‌های با سرپرست زن
        $femaleHeadFamiliesCount = Family::where('charity_id', $charityId)
            ->whereHas('head', function($q) {
                $q->where('gender', 'female');
            })
            ->count();

        $this->criteriaData = [
            [
                'name' => 'خانواده‌های محروم',
                'count' => $deprivedFamiliesCount,
                'percentage' => $totalFamilies > 0 ? round(($deprivedFamiliesCount / $totalFamilies) * 100, 1) : 0,
                'color' => '#ef4444',
                'type' => 'family'
            ],
            [
                'name' => 'سرپرستی زن',
                'count' => $femaleHeadFamiliesCount,
                'percentage' => $totalFamilies > 0 ? round(($femaleHeadFamiliesCount / $totalFamilies) * 100, 1) : 0,
                'color' => '#3b82f6',
                'type' => 'family'
            ],
            [
                'name' => 'افراد معلول',
                'count' => Member::whereHas('family', function($q) use ($charityId) {
                    $q->where('charity_id', $charityId);
                })->where('has_disability', 1)->count(),
                'percentage' => $totalMembers > 0 ? round((Member::whereHas('family', function($q) use ($charityId) {
                    $q->where('charity_id', $charityId);
                })->where('has_disability', 1)->count() / $totalMembers) * 100, 1) : 0,
                'color' => '#10b981',
                'type' => 'individual'
            ],
            [
                'name' => 'بیماری مزمن',
                'count' => Member::whereHas('family', function($q) use ($charityId) {
                    $q->where('charity_id', $charityId);
                })->where('has_chronic_disease', 1)->count(),
                'percentage' => $totalMembers > 0 ? round((Member::whereHas('family', function($q) use ($charityId) {
                    $q->where('charity_id', $charityId);
                })->where('has_chronic_disease', 1)->count() / $totalMembers) * 100, 1) : 0,
                'color' => '#8b5cf6',
                'type' => 'individual'
            ]
        ];
    }

    public function render()
    {
        return view('livewire.charity.dashboard-stats');
    }
}
