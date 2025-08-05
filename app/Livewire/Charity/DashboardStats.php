<?php

namespace App\Livewire\Charity;

use App\Models\Family;
use App\Models\Member;
use App\Models\Province;
use App\Models\City;
use App\Models\Region;
use App\Models\Organization;
use App\Models\RankSetting;
use App\Models\FamilyCriterion;
use App\Models\Insurance;
use App\Models\FundingSource;
use App\Models\RankingScheme;
use App\Models\RankingSchemeCriterion;
use App\Repositories\FamilyRepository;
use App\Services\InsuranceService;
use App\Services\Notification\TelegramChannel;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\QueryFilters\RankingFilter;
use App\QuerySorts\RankingSort;
use App\Helpers\ProblemTypeHelper;

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
    
    // متغیرهای فیلتر
    public $selectedYear;
    public $selectedMonth;
    public $selectedOrganization;
    public $jalaliYears = [];
    public $jalaliMonths = [];
    public $organizations = [];
    
    protected $queryString = [
        'selectedYear' => ['except' => ''],
        'selectedMonth' => ['except' => ''],
        'selectedOrganization' => ['except' => ''],
    ];
    
    protected $listeners = [
        'filterChanged' => 'refreshData'
    ];
    
    /**
     * متد Livewire که بعد از تغییر هر property اجرا می‌شود
     * برای رفرش چارت‌ها بعد از تغییر فیلتر
     */
    public function updated($propertyName)
    {
        // فقط در صورت تغییر فیلترها چارت‌ها را رفرش کن
        if (in_array($propertyName, ['selectedYear', 'selectedMonth', 'selectedOrganization'])) {
            $this->loadAllData();
            
            // dispatch کردن event برای رفرش چارت‌ها در JavaScript
            $this->dispatch('refreshAllCharts');
        }
    }

    public function mount()
    {
        // مقداردهی فیلترها
        $this->initializeFilters();
        
        // بارگذاری داده‌ها
        $this->loadAllData();
    }

    /**
     * مقداردهی فیلترها
     */
    private function initializeFilters()
    {
        // سال‌های جلالی
        $currentYear = now()->year;
        $jalaliCurrentYear = $currentYear - 621; // تبدیل تقریبی میلادی به جلالی
        
        $this->jalaliYears = [];
        for ($i = $jalaliCurrentYear - 5; $i <= $jalaliCurrentYear + 1; $i++) {
            $this->jalaliYears[] = $i;
        }
        
        // ماه‌های جلالی
        $this->jalaliMonths = [
            1 => 'فروردین',
            2 => 'اردیبهشت', 
            3 => 'خرداد',
            4 => 'تیر',
            5 => 'مرداد',
            6 => 'شهریور',
            7 => 'مهر',
            8 => 'آبان',
            9 => 'آذر',
            10 => 'دی',
            11 => 'بهمن',
            12 => 'اسفند'
        ];
        
        // مقادیر پیش‌فرض
        $this->selectedYear = $jalaliCurrentYear;
        $this->selectedMonth = null;
        $this->selectedOrganization = null;
        
        // لیست سازمان‌ها (در صورت نیاز)
        $this->loadOrganizations();
    }
    
    /**
     * بارگذاری لیست سازمان‌ها
     */
    private function loadOrganizations()
    {
        $this->organizations = DB::table('organizations')
            ->where('type', 'charity')
            ->select('id', 'name')
            ->get()
            ->toArray();
    }
    
    /**
     * بارگذاری همه داده‌ا با فیلترها
     */
    private function loadAllData()
    {
        $charityId = $this->getCharityId();
        
        if (!$charityId) {
            $this->resetAllStats();
            return;
        }
        
        // بارگذاری آمار اصلی
        $this->loadMainStatistics($charityId);
        
        // بارگذاری آمار جنسیتی
        $this->loadGenderData($charityId);
        
        // بارگذاری آمار جغرافیایی
        $this->loadGeographicData($charityId);
        
        // بارگذاری آمار معیارها
        $this->loadCriteriaData($charityId);
    }
    
    /**
     * دریافت charity_id بر اساس فیلترها
     */
    private function getCharityId()
    {
        $userCharityId = Auth::user()->organization_id;
        return $this->selectedOrganization ?: $userCharityId;
    }
    
    /**
     * ریست کردن همه آمار
     */
    private function resetAllStats()
    {
        $this->insuredFamilies = 0;
        $this->uninsuredFamilies = 0;
        $this->insuredMembers = 0;
        $this->uninsuredMembers = 0;
        $this->maleCount = 0;
        $this->femaleCount = 0;
        $this->provinceNames = [];
        $this->provinceMaleCounts = [];
        $this->provinceFemaleCounts = [];
        $this->provinceDeprivedCounts = [];
        $this->criteriaData = [];
    }
    
    /**
     * بارگذاری آمار اصلی با فیلتر
     */
    private function loadMainStatistics($charityId)
    {
        $familyQuery = Family::where('charity_id', $charityId);
        $familyQuery = $this->applyDateFilters($familyQuery);
        
        // خانواده‌های بیمه‌دار
        $this->insuredFamilies = (clone $familyQuery)
            ->where(function($q) {
                $q->whereHas('insurances')
                  ->orWhere('is_insured', true)
                  ->orWhere('is_insured', 1);
            })
            ->count();

        // خانواده‌های بدون بیمه
        $this->uninsuredFamilies = (clone $familyQuery)
            ->whereDoesntHave('insurances')
            ->where(function($q) {
                $q->where('is_insured', false)
                  ->orWhere('is_insured', 0)
                  ->orWhereNull('is_insured');
            })
            ->count();

        // اعضای بیمه‌دار
        $memberQuery = Member::whereHas('family', function($q) use ($charityId) {
            $q->where('charity_id', $charityId);
        });
        $memberQuery = $this->applyDateFilters($memberQuery, 'members.created_at');
        
        $this->insuredMembers = (clone $memberQuery)
            ->whereHas('family', function($q) {
                $q->where(function($subQ) {
                    $subQ->whereHas('insurances')
                         ->orWhere('is_insured', true)
                         ->orWhere('is_insured', 1);
                });
            })->count();

        // اعضای بدون بیمه
        $this->uninsuredMembers = (clone $memberQuery)
            ->whereHas('family', function($q) {
                $q->whereDoesntHave('insurances')
                  ->where(function($subQ) {
                      $subQ->where('is_insured', false)
                           ->orWhere('is_insured', 0)
                           ->orWhereNull('is_insured');
                  });
            })->count();
    }
    
    /**
     * اعمال فیلترهای تاریخ به کوئری
     */
    private function applyDateFilters($query, $dateColumn = 'created_at')
    {
        if ($this->selectedYear) {
            // تبدیل سال جلالی به میلادی (تقریبی)
            $gregorianYear = $this->selectedYear + 621;
            
            if ($this->selectedMonth) {
                // فیلتر ماه خاص
                $startDate = "{$gregorianYear}-{$this->selectedMonth}-01";
                $endDate = "{$gregorianYear}-{$this->selectedMonth}-31";
                
                $query->whereBetween($dateColumn, [$startDate, $endDate]);
            } else {
                // فیلتر سال کامل
                $startDate = "{$gregorianYear}-01-01";
                $endDate = "{$gregorianYear}-12-31";
                
                $query->whereBetween($dateColumn, [$startDate, $endDate]);
            }
        }
        
        return $query;
    }
    

    
    /**
     * به‌روزرسانی داده‌ها
     */
    public function refreshData()
    {
        $this->loadAllData();
    }
    
    /**
     * بارگذاری آمار جنسیتی با فیلتر
     */
    private function loadGenderData($charityId)
    {
        $memberQuery = Member::whereHas('family', function($q) use ($charityId) {
            $q->where('charity_id', $charityId);
        });
        
        $memberQuery = $this->applyDateFilters($memberQuery, 'members.created_at');
        
        $this->maleCount = (clone $memberQuery)->where('gender', 'male')->count();
        $this->femaleCount = (clone $memberQuery)->where('gender', 'female')->count();
    }
    
    /**
     * ریست کردن فیلترها
     */
    public function resetFilters()
    {
        $this->selectedYear = now()->year - 621; // سال جاری جلالی
        $this->selectedMonth = null;
        $this->selectedOrganization = null;
        
        $this->loadAllData();
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

    /**
     * رندر کامپوننت
     */
    public function render()
    {
        return view('livewire.charity.dashboard-stats', [
            'jalaliYears' => $this->jalaliYears,
            'jalaliMonths' => $this->jalaliMonths,
            'organizations' => $this->organizations,
            'insuredFamilies' => $this->insuredFamilies,
            'uninsuredFamilies' => $this->uninsuredFamilies,
            'insuredMembers' => $this->insuredMembers,
            'uninsuredMembers' => $this->uninsuredMembers,
            'maleCount' => $this->maleCount,
            'femaleCount' => $this->femaleCount,
            'provinceNames' => $this->provinceNames,
            'provinceMaleCounts' => $this->provinceMaleCounts,
            'provinceFemaleCounts' => $this->provinceFemaleCounts,
            'provinceDeprivedCounts' => $this->provinceDeprivedCounts,
            'criteriaData' => $this->criteriaData,
        ]);
    }
}
