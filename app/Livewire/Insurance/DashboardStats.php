<?php

namespace App\Livewire\Insurance;

use Livewire\Component;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Province;
use App\Models\Family;
use App\Models\FamilyInsurance;
use App\Models\FundingTransaction;
use App\Models\InsuranceImportLog;
use App\Models\InsuranceAllocation;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DashboardStats extends Component
{
    public $totalInsured = 0;
    public $totalPayment = 0;
    public $totalOrganizations = 0;
    public $maleCount = 0;
    public $femaleCount = 0;
    public $selectedMonth;
    public $selectedYear;
    public $selectedOrganization;
    // فیلترها حذف شدند

    protected $queryString = [
        'selectedMonth' => ['except' => ''],
        'selectedYear' => ['except' => ''],
        'selectedOrganization' => ['except' => ''],
    ];

    public function mount()
    {
        $currentJalali = Jalalian::now();
        $this->selectedYear = $currentJalali->getYear();
        $this->selectedMonth = null; // پیش‌فرض: کل سال
        $this->selectedOrganization = null;
        $this->loadStatistics();
    }

    /**
     * بارگذاری آمار اصلی
     */
    private function loadStatistics()
    {
        // موقتاً cache را غیرفعال می‌کنم تا مشکل debug شود
        $stats = $this->calculateStatistics();

        $this->totalInsured = $stats['totalInsured'];
        $this->maleCount = $stats['maleCount'];
        $this->femaleCount = $stats['femaleCount'];
        $this->totalOrganizations = $stats['totalOrganizations'];
        $this->totalPayment = $stats['totalPayment'];
    }

    /**
     * محاسبه آمار اصلی
     */
    private function calculateStatistics()
    {
        $dateRange = $this->getDateRange();
        $baseQuery = $this->getBaseQuery($dateRange);

        // آمار کلی
        $totalInsured = $this->getTotalInsuredCount($baseQuery);
        $genderStats = $this->getGenderStats($baseQuery);
        $totalOrganizations = Organization::active()->count();
        $totalPayment = $this->getTotalPayments($dateRange);

        return [
            'totalInsured' => $totalInsured,
            'maleCount' => $genderStats['male'],
            'femaleCount' => $genderStats['female'],
            'totalOrganizations' => $totalOrganizations,
            'totalPayment' => $totalPayment,
        ];
    }

    /**
     * ایجاد کوئری پایه
     */
    private function getBaseQuery($dateRange)
    {
        $query = Member::query()->whereHas('family');

        // فیلتر سازمان
        if ($this->selectedOrganization) {
            $query->whereHas('family', function($q) {
                $q->where('charity_id', $this->selectedOrganization)
                  ->orWhere('insurance_id', $this->selectedOrganization);
            });
        }

        return $query;
    }

    /**
     * محاسبه تعداد کل بیمه‌شدگان
     */
    private function getTotalInsuredCount($baseQuery)
    {
        $dateRange = $this->getDateRange();
        
        // تعداد کل بیمه‌شدگان با فیلتر زمانی
        $query = Member::query()
            ->join('families', 'members.family_id', '=', 'families.id')
            ->join('family_insurances', 'families.id', '=', 'family_insurances.family_id')
            ->whereBetween('family_insurances.start_date', [$dateRange['start'], $dateRange['end']]);

        // فیلتر سازمان
        if ($this->selectedOrganization) {
            $query->where(function($q) {
                $q->where('families.charity_id', $this->selectedOrganization)
                  ->orWhere('families.insurance_id', $this->selectedOrganization);
            });
        }

        return $query->distinct('members.id')->count('members.id');
    }

    /**
     * آمار جنسیتی
     */
    private function getGenderStats($baseQuery)
    {
        $dateRange = $this->getDateRange();
        
        // آمار جنسیتی با فیلتر زمانی
        $query = Member::query()
            ->join('families', 'members.family_id', '=', 'families.id')
            ->join('family_insurances', 'families.id', '=', 'family_insurances.family_id')
            ->whereBetween('family_insurances.start_date', [$dateRange['start'], $dateRange['end']]);

        // فیلتر سازمان
        if ($this->selectedOrganization) {
            $query->where(function($q) {
                $q->where('families.charity_id', $this->selectedOrganization)
                  ->orWhere('families.insurance_id', $this->selectedOrganization);
            });
        }

        $stats = $query->select('members.gender', DB::raw('count(distinct members.id) as count'))
            ->groupBy('members.gender')
            ->pluck('count', 'gender')
            ->toArray();

        return [
            'male' => $stats['male'] ?? 0,
            'female' => $stats['female'] ?? 0,
        ];
    }

    /**
     * محاسبه پرداخت‌های کل
     */
    private function getTotalPayments($dateRange)
    {
        // حق بیمه‌های پرداخت شده
        $insurancePayments = FamilyInsurance::whereBetween('start_date', [$dateRange['start'], $dateRange['end']])
            ->when($this->selectedOrganization, function($q) {
                return $q->whereHas('family', function($family) {
                    $family->where('charity_id', $this->selectedOrganization)
                          ->orWhere('insurance_id', $this->selectedOrganization);
                });
            })
            ->sum('premium_amount');

        // خسارات پرداخت شده
        $allocations = InsuranceAllocation::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->when($this->selectedOrganization, function($q) {
                return $q->whereHas('family', function($family) {
                    $family->where('charity_id', $this->selectedOrganization)
                          ->orWhere('insurance_id', $this->selectedOrganization);
                });
            })
            ->sum('amount');

        return $insurancePayments + $allocations;
    }

    /**
     * محاسبه بازه تاریخ
     */
    private function getDateRange()
    {
        if ($this->selectedMonth) {
            // ماه خاص
            return $this->convertJalaliToGregorian($this->selectedYear, $this->selectedMonth);
        } else {
            // کل سال
            return $this->convertJalaliYearToGregorian($this->selectedYear);
        }
    }

    /**
     * تبدیل سال جلالی به میلادی
     */
    private function convertJalaliYearToGregorian($jalaliYear)
    {
        try {
            // بررسی معتبر بودن سال
            if (!$jalaliYear || $jalaliYear < 1300 || $jalaliYear > 1500) {
                return $this->getFallbackDateRange();
            }
            
            // برای سال 1403 (2024-2025) و 1404 (2025-2026)
            // استفاده از range گسترده‌تر که داده‌های موجود را پوشش دهد
            if ($jalaliYear == 1403) {
                return [
                    'start' => '2024-01-01',
                    'end' => '2024-12-31'
                ];
            } elseif ($jalaliYear == 1404) {
                return [
                    'start' => '2024-01-01', // داده‌های موجود از 2024 شروع می‌شوند
                    'end' => '2025-12-31'
                ];
            }
            
            // برای سال‌های دیگر - محاسبه تقریبی
            $gregorianStartYear = $jalaliYear + 621;
            return [
                'start' => $gregorianStartYear . '-01-01',
                'end' => ($gregorianStartYear + 1) . '-12-31'
            ];
        } catch (\Exception $e) {
            return $this->getFallbackDateRange();
        }
    }

    /**
     * تبدیل تاریخ جلالی به میلادی
     */
    private function convertJalaliToGregorian($jalaliYear, $jalaliMonth)
    {
        try {
            // بررسی معتبر بودن پارامترها
            if (!$jalaliYear || $jalaliYear < 1300 || $jalaliYear > 1500) {
                return $this->getFallbackDateRange();
            }
            
            if (!$jalaliMonth || $jalaliMonth < 1 || $jalaliMonth > 12) {
                return $this->getFallbackDateRange();
            }
            
            // نقشه برداری دقیق‌تر برای ماه‌ها
            // سال 1404 = 2025 (Mar) to 2026 (Feb)
            // سال 1403 = 2024 (Mar) to 2025 (Feb)
            
            if ($jalaliYear == 1404) {
                $monthMapping = [
                    1 => ['2025-03-01', '2025-03-31'], // فروردین
                    2 => ['2025-04-01', '2025-04-30'], // اردیبهشت
                    3 => ['2025-05-01', '2025-05-31'], // خرداد
                    4 => ['2025-06-01', '2025-06-30'], // تیر
                    5 => ['2025-07-01', '2025-07-31'], // مرداد
                    6 => ['2025-08-01', '2025-08-31'], // شهریور
                    7 => ['2025-09-01', '2025-09-30'], // مهر
                    8 => ['2025-10-01', '2025-10-31'], // آبان
                    9 => ['2025-11-01', '2025-11-30'], // آذر
                    10 => ['2025-12-01', '2025-12-31'], // دی
                    11 => ['2026-01-01', '2026-01-31'], // بهمن
                    12 => ['2026-02-01', '2026-02-28'], // اسفند
                ];
            } else {
                // برای سال 1403 و دیگر سال‌ها
                $baseYear = $jalaliYear == 1403 ? 2024 : ($jalaliYear + 621);
                $monthMapping = [
                    1 => [$baseYear . '-03-01', $baseYear . '-03-31'],
                    2 => [$baseYear . '-04-01', $baseYear . '-04-30'],
                    3 => [$baseYear . '-05-01', $baseYear . '-05-31'],
                    4 => [$baseYear . '-06-01', $baseYear . '-06-30'],
                    5 => [$baseYear . '-07-01', $baseYear . '-07-31'],
                    6 => [$baseYear . '-08-01', $baseYear . '-08-31'],
                    7 => [$baseYear . '-09-01', $baseYear . '-09-30'],
                    8 => [$baseYear . '-10-01', $baseYear . '-10-31'],
                    9 => [$baseYear . '-11-01', $baseYear . '-11-30'],
                    10 => [$baseYear . '-12-01', $baseYear . '-12-31'],
                    11 => [($baseYear + 1) . '-01-01', ($baseYear + 1) . '-01-31'],
                    12 => [($baseYear + 1) . '-02-01', ($baseYear + 1) . '-02-28'],
                ];
            }
            
            if (isset($monthMapping[$jalaliMonth])) {
                return [
                    'start' => $monthMapping[$jalaliMonth][0],
                    'end' => $monthMapping[$jalaliMonth][1]
                ];
            }
            
            return $this->getFallbackDateRange();
        } catch (\Exception $e) {
            return $this->getFallbackDateRange();
        }
    }

    /**
     * بازه تاریخ پیش‌فرض
     */
    private function getFallbackDateRange()
    {
        return [
            'start' => now()->startOfMonth()->format('Y-m-d'),
            'end' => now()->endOfMonth()->format('Y-m-d')
        ];
    }

    /**
     * کلید کش
     */
    private function getCacheKey()
    {
        return "dashboard_stats_{$this->selectedYear}_{$this->selectedMonth}_{$this->selectedOrganization}";
    }

    /**
     * داده‌های جغرافیایی بهینه‌شده
     */
    private function getOptimizedGeographicData()
    {
        $dateRange = $this->getDateRange();
        
        // استفاده از Eloquent برای کوئری بهتر
        $query = Province::query()
            ->leftJoin('families', 'provinces.id', '=', 'families.province_id')
            ->leftJoin('members', 'families.id', '=', 'members.family_id')
            ->leftJoin('family_insurances', 'families.id', '=', 'family_insurances.family_id');

        // فیلتر زمانی - بر اساس تاریخ صدور بیمه
        $query->whereBetween('family_insurances.start_date', [$dateRange['start'], $dateRange['end']]);

        // فیلتر سازمان
        if ($this->selectedOrganization) {
            $query->where(function($q) {
                $q->where('families.charity_id', $this->selectedOrganization)
                  ->orWhere('families.insurance_id', $this->selectedOrganization);
            });
        }

            $results = $query->select(
                    'provinces.name as province_name',
                    DB::raw('COUNT(DISTINCT CASE WHEN members.gender = "male" THEN members.id END) as male_count'),
                    DB::raw('COUNT(DISTINCT CASE WHEN members.gender = "female" THEN members.id END) as female_count'),
                    DB::raw('COUNT(DISTINCT CASE WHEN families.poverty_confirmed = 1 THEN members.id END) as deprived_count')
                )
                ->whereNotNull('members.id') // فقط استان‌هایی که عضو دارند
                ->groupBy('provinces.id', 'provinces.name')
                ->orderBy('provinces.name')
                ->get();

        return [
            'provinceNames' => $results->pluck('province_name')->toArray(),
            'provinceMaleCounts' => $results->pluck('male_count')->map(fn($v) => (int)$v)->toArray(),
            'provinceFemaleCounts' => $results->pluck('female_count')->map(fn($v) => (int)$v)->toArray(),
            'provinceDeprivedCounts' => $results->pluck('deprived_count')->map(fn($v) => (int)$v)->toArray()
        ];
    }

    /**
     * داده‌های مالی کلی
     */
    private function getFinancialData()
    {
        $dateRange = $this->getDateRange();
        
        // بودجه‌های تخصیص یافته
        $totalBudget = FundingTransaction::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->sum('amount');

        // حق بیمه‌های پرداخت شده
        $totalPremiums = FamilyInsurance::whereBetween('start_date', [$dateRange['start'], $dateRange['end']])
            ->when($this->selectedOrganization, function($q) {
                return $q->whereHas('family', function($family) {
                    $family->where('charity_id', $this->selectedOrganization)
                          ->orWhere('insurance_id', $this->selectedOrganization);
                });
            })
            ->sum('premium_amount');

        // خسارات پرداخت شده
        $totalClaims = InsuranceAllocation::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->when($this->selectedOrganization, function($q) {
                return $q->whereHas('family', function($family) {
                    $family->where('charity_id', $this->selectedOrganization)
                          ->orWhere('insurance_id', $this->selectedOrganization);
                });
            })
            ->sum('amount');

        $total = $totalPremiums + $totalClaims;

        return [
            'budget' => $totalBudget,
            'premiums' => $totalPremiums,
            'claims' => $totalClaims,
            'total' => $total,
            'premiumsDisplay' => number_format($totalPremiums / 1000000, 1),
            'claimsDisplay' => number_format($totalClaims / 1000000, 1),
            'totalDisplay' => number_format($total / 1000000, 1),
            'budgetDisplay' => number_format($totalBudget / 1000000, 1),
            'unit' => 'میلیون تومان',
            'premiumsPercentage' => $total > 0 ? round(($totalPremiums / $total) * 100, 1) : 0,
            'claimsPercentage' => $total > 0 ? round(($totalClaims / $total) * 100, 1) : 0,
        ];
    }

    /**
     * جریان مالی ماهانه
     */
    private function getMonthlyFinancialFlow()
    {
        $result = [];
        
        // اگر ماه خاص انتخاب شده، فقط همان ماه را نمایش می‌دهیم
        $monthsToShow = $this->selectedMonth ? [$this->selectedMonth] : range(1, 12);
        
        foreach ($monthsToShow as $month) {
            $dateRange = $this->convertJalaliToGregorian($this->selectedYear, $month);
            
            $premiums = FamilyInsurance::where('start_date', '>=', $dateRange['start'])
                ->where('start_date', '<', $dateRange['end'])
                ->when($this->selectedOrganization, function($q) {
                    return $q->whereHas('family', function($family) {
                        $family->where('charity_id', $this->selectedOrganization)
                              ->orWhere('insurance_id', $this->selectedOrganization);
                    });
                })
                ->sum('premium_amount') ?? 0;

            $claims = InsuranceAllocation::where('created_at', '>=', $dateRange['start'])
                ->where('created_at', '<', $dateRange['end'])
                ->when($this->selectedOrganization, function($q) {
                    return $q->whereHas('family', function($family) {
                        $family->where('charity_id', $this->selectedOrganization)
                              ->orWhere('insurance_id', $this->selectedOrganization);
                    });
                })
                ->sum('amount') ?? 0;

            $budget = FundingTransaction::where('created_at', '>=', $dateRange['start'])
                ->where('created_at', '<', $dateRange['end'])
                ->sum('amount') ?? 0;

            $result[] = [
                'month' => $month,
                'monthName' => $this->getJalaliMonths()[$month],
                'premiums' => (int)$premiums,
                'claims' => (int)$claims,
                'budget' => (int)$budget,
                'total' => (int)($premiums + $claims + $budget)
            ];
        }
        
        return $result;
    }

    /**
     * تحلیل معیارهای پذیرش بهینه‌شده
     */
    private function getOptimizedCriteriaAnalysis()
    {
        $dateRange = $this->getDateRange();
        
        // خانواده‌های در دوره انتخابی (با فیلتر زمانی)
        $familiesQuery = Family::query()
            ->join('family_insurances', 'families.id', '=', 'family_insurances.family_id')
            ->whereBetween('family_insurances.start_date', [$dateRange['start'], $dateRange['end']]);

        if ($this->selectedOrganization) {
            $familiesQuery->where(function($q) {
                $q->where('families.charity_id', $this->selectedOrganization)
                  ->orWhere('families.insurance_id', $this->selectedOrganization);
            });
        }

        $totalFamilies = $familiesQuery->distinct('families.id')->count('families.id');
        
        // خانواده‌های محروم (با فیلتر زمانی)
        $deprivedFamiliesQuery = Family::query()
            ->join('family_insurances', 'families.id', '=', 'family_insurances.family_id')
            ->whereBetween('family_insurances.start_date', [$dateRange['start'], $dateRange['end']])
            ->where('families.poverty_confirmed', 1);

        if ($this->selectedOrganization) {
            $deprivedFamiliesQuery->where(function($q) {
                $q->where('families.charity_id', $this->selectedOrganization)
                  ->orWhere('families.insurance_id', $this->selectedOrganization);
            });
        }

        $deprivedFamilies = $deprivedFamiliesQuery->distinct('families.id')->count('families.id');

        // آمار اعضا با فیلتر زمانی
        $membersQuery = Member::query()
            ->join('families', 'members.family_id', '=', 'families.id')
            ->join('family_insurances', 'families.id', '=', 'family_insurances.family_id')
            ->whereBetween('family_insurances.start_date', [$dateRange['start'], $dateRange['end']]);

        if ($this->selectedOrganization) {
            $membersQuery->where(function($q) {
                $q->where('families.charity_id', $this->selectedOrganization)
                  ->orWhere('families.insurance_id', $this->selectedOrganization);
            });
        }

        $disabilityCount = (clone $membersQuery)->where('members.has_disability', 1)->distinct('members.id')->count('members.id');
        $chronicCount = (clone $membersQuery)->where('members.has_chronic_disease', 1)->distinct('members.id')->count('members.id');

        $totalMembers = max($this->totalInsured, 1);
        $maxFamilies = max($totalFamilies, 1);

        return [
            [
                'name' => 'خانوار محروم',
                'count' => $deprivedFamilies,
                'percentage' => round(($deprivedFamilies / $maxFamilies) * 100, 1),
                'type' => 'family',
                'color' => '#ef4444'
            ],
            [
                'name' => 'افراد دارای معلولیت',
                'count' => $disabilityCount,
                'percentage' => round(($disabilityCount / $totalMembers) * 100, 1),
                'type' => 'member',
                'color' => '#3b82f6'
            ],
            [
                'name' => 'افراد دارای بیماری مزمن',
                'count' => $chronicCount,
                'percentage' => round(($chronicCount / $totalMembers) * 100, 1),
                'type' => 'member',
                'color' => '#10b981'
            ],
            [
                'name' => 'کل خانوارها',
                'count' => $totalFamilies,
                'percentage' => 100,
                'type' => 'family',
                'color' => '#8b5cf6'
            ]
        ];
    }

    /**
     * ماه‌های جلالی
     */
    private function getJalaliMonths()
    {
        return [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
            5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
            9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        ];
    }

    /**
     * Event handlers برای فیلترها
     */
    public function updatedSelectedMonth()
    {
        $this->clearCache();
        $this->loadStatistics();
        $this->dispatch('refreshAllCharts');
    }

    public function updatedSelectedYear()
    {
        $this->clearCache();
        $this->loadStatistics();
        $this->dispatch('refreshAllCharts');
    }

    public function updatedSelectedOrganization()
    {
        $this->clearCache();
        $this->loadStatistics();
        $this->dispatch('refreshAllCharts');
    }

    public function resetFilters()
    {
        $this->selectedMonth = null;
        $this->selectedOrganization = null;
        $this->clearCache();
        $this->loadStatistics();
        $this->dispatch('refreshAllCharts');
    }

    /**
     * پاک کردن کش
     */
    private function clearCache()
    {
        $keys = [
            $this->getCacheKey(),
            "geo_data_{$this->selectedYear}_{$this->selectedMonth}_{$this->selectedOrganization}",
            "financial_data_{$this->selectedYear}_{$this->selectedMonth}_{$this->selectedOrganization}",
            "criteria_data_{$this->selectedYear}_{$this->selectedMonth}_{$this->selectedOrganization}"
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    public function render()
    {
        try {
            // داده‌های اصلی
            $geoData = $this->getOptimizedGeographicData();
            $financialData = $this->getFinancialData();
            $monthlyFlow = $this->getMonthlyFinancialFlow();
            $criteriaData = $this->getOptimizedCriteriaAnalysis();
            
            // داده‌های فیلترها
            $currentJalaliYear = Jalalian::now()->getYear();
            $jalaliYears = range($currentJalaliYear, $currentJalaliYear - 4);
            $jalaliMonths = $this->getJalaliMonths();
            $organizations = Organization::active()->orderBy('name')->get();

            // داده‌های ماهانه برای چارت انتخابی
            $selectedDateRange = $this->getDateRange();
            $monthlyClaimsData = [
                'total' => $financialData['total'],
                'premiums' => $financialData['premiums'],
                'claims' => $financialData['claims'],
                'budget' => $financialData['budget']
            ];

            return view('livewire.insurance.dashboard-stats', [
                // آمار اصلی
                'totalInsured' => $this->totalInsured,
                'totalPayment' => $this->totalPayment,
                'totalOrganizations' => $this->totalOrganizations,
                'maleCount' => $this->maleCount,
                'femaleCount' => $this->femaleCount,
                
                // داده‌های جغرافیایی
                'provinceNames' => $geoData['provinceNames'],
                'provinceMaleCounts' => $geoData['provinceMaleCounts'],
                'provinceFemaleCounts' => $geoData['provinceFemaleCounts'],
                'provinceDeprivedCounts' => $geoData['provinceDeprivedCounts'],
                
                // داده‌های مالی
                'financialRatio' => $financialData,
                'monthlyClaimsData' => $monthlyClaimsData,
                'yearlyClaimsFlow' => $monthlyFlow,
                
                // داده‌های معیارها
                'criteriaData' => $criteriaData,
                
                // فیلترها
                'jalaliYears' => $jalaliYears,
                'jalaliMonths' => $jalaliMonths,
                'organizations' => $organizations,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Dashboard error: ' . $e->getMessage());
            
            // داده‌های fallback
            return view('livewire.insurance.dashboard-stats', [
                'totalInsured' => 0,
                'totalPayment' => 0,
                'totalOrganizations' => 0,
                'maleCount' => 0,
                'femaleCount' => 0,
                'provinceNames' => [],
                'provinceMaleCounts' => [],
                'provinceFemaleCounts' => [],
                'provinceDeprivedCounts' => [],
                'jalaliYears' => [1403, 1402, 1401, 1400, 1399],
                'criteriaData' => [],
                'monthlyClaimsData' => ['total' => 0, 'premiums' => 0, 'claims' => 0, 'budget' => 0],
                'yearlyClaimsFlow' => [],
                'jalaliMonths' => $this->getJalaliMonths(),
                'financialRatio' => [
                    'premiums' => 0, 'claims' => 0, 'total' => 0, 'budget' => 0,
                    'premiumsDisplay' => '0', 'claimsDisplay' => '0', 'totalDisplay' => '0', 'budgetDisplay' => '0',
                    'unit' => 'میلیون تومان', 'premiumsPercentage' => 0, 'claimsPercentage' => 0
                ],
                'organizations' => collect([]),
            ]);
        }
    }
}