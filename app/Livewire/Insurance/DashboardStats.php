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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ProblemTypeHelper;

class DashboardStats extends Component
{
    // خصوصیات مشترک
    public $totalInsured = 0;
    public $totalPayment = 0;
    public $totalOrganizations = 0;
    public $maleCount = 0;
    public $femaleCount = 0;
    public $selectedMonth;
    public $selectedYear;
    public $selectedOrganization;
    
    // خصوصیات خیریه
    public $insuredFamilies = 0;
    public $uninsuredFamilies = 0;
    public $insuredMembers = 0;
    public $uninsuredMembers = 0;
    public $totalFamilies = 0;
    public $totalDeprived = 0;
    public $pendingFamilies = 0;
    
    // نوع پنل (تشخیص خودکار)
    public $panelType = 'insurance'; // 'insurance' یا 'charity'
    
    // خصوصیت جدید برای کنترل نمایش بخش‌های مالی
    public $showFinancialData = true;

    protected $queryString = [
        'selectedMonth' => ['except' => ''],
        'selectedYear' => ['except' => ''],
        'selectedOrganization' => ['except' => ''],
    ];

    public function mount($panelType = null)
    {
        try {
            // تشخیص نوع پنل
            $this->panelType = $panelType ?: $this->detectPanelType();
            
            // تعیین نمایش داده‌های مالی بر اساس نوع پنل
            $this->showFinancialData = ($this->panelType === 'insurance');
            
            $currentJalali = Jalalian::now();
            $this->selectedYear = $currentJalali->getYear();
            $this->selectedMonth = null; // پیش‌فرض: کل سال
            $this->selectedOrganization = null;
            
            $this->loadStatistics();
            
            Log::info('🚀 Dashboard component mounted successfully', [
                'panel_type' => $this->panelType,
                'show_financial' => $this->showFinancialData,
                'user_id' => auth()->id()
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error mounting dashboard component', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);
            
            // مقادیر پیش‌فرض در صورت خطا
            $this->panelType = 'insurance';
            $this->showFinancialData = true;
            $this->selectedYear = 1403;
        }
    }

    /**
     * تشخیص نوع پنل بر اساس کاربر یا مسیر
     */
    private function detectPanelType()
    {
        $user = Auth::user();
        
        // بر اساس نقش کاربری
        if ($user->isActiveAs('charity') || 
            $user->organization?->type === 'charity' ||
            request()->is('charity/*')) {
            return 'charity';
        }
        
        return 'insurance';
    }

    /**
     * بارگذاری آمار اصلی
     */
    private function loadStatistics()
    {
        $startTime = microtime(true);
        $traceId = uniqid('DASH_LOAD_', true);
        
        try {
            Log::info("[{$traceId}] 📊 Starting dashboard statistics loading", [
                'panel_type' => $this->panelType,
                'filters' => [
                    'year' => $this->selectedYear,
                    'month' => $this->selectedMonth,
                    'organization' => $this->selectedOrganization
                ],
                'user_id' => auth()->id(),
                'timestamp' => now()->toISOString()
            ]);
            
            // ساخت کلید کش بر اساس فیلترها و نوع پنل
            $cacheKey = "{$this->panelType}_dashboard_stats_{$this->selectedYear}_{$this->selectedMonth}_{$this->selectedOrganization}";

            // استفاده از کش با زمان منقضی شدن 6 ساعت
            $stats = Cache::remember($cacheKey, now()->addHours(6), function () use ($traceId) {
                Log::info("[{$traceId}] 🔄 Cache miss - calculating fresh statistics");
                return $this->calculateStatistics();
            });

            // تنظیم مقادیر مشترک
            $this->totalInsured = $stats['totalInsured'] ?? 0;
            $this->maleCount = $stats['maleCount'] ?? 0;
            $this->femaleCount = $stats['femaleCount'] ?? 0;
            $this->totalOrganizations = $stats['totalOrganizations'] ?? 0;
            
            // مقادیر مالی فقط برای پنل بیمه
            if ($this->showFinancialData) {
                $this->totalPayment = $stats['totalPayment'] ?? 0;
            }

            // مقادیر خاص خیریه
            if ($this->panelType === 'charity') {
                $this->insuredFamilies = $stats['insuredFamilies'] ?? 0;
                $this->uninsuredFamilies = $stats['uninsuredFamilies'] ?? 0;
                $this->insuredMembers = $stats['insuredMembers'] ?? 0;
                $this->uninsuredMembers = $stats['uninsuredMembers'] ?? 0;
                $this->totalFamilies = $stats['totalFamilies'] ?? 0;
                $this->totalDeprived = $stats['totalDeprived'] ?? 0;
                $this->pendingFamilies = $stats['pendingFamilies'] ?? 0;
                
                Log::info("[{$traceId}] ✅ Charity statistics loaded successfully", [
                    'total_families' => $this->totalFamilies,
                    'insured_families' => $this->insuredFamilies,
                    'uninsured_families' => $this->uninsuredFamilies,
                    'execution_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
                ]);
            } else {
                // تنظیم مقادیر بیمه
                $this->totalPayment = $stats['totalPayment'] ?? 0;
                
                Log::info("[{$traceId}] ✅ Insurance statistics loaded successfully", [
                    'total_insured' => $this->totalInsured,
                    'total_payment' => $this->totalPayment,
                    'execution_time' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
                ]);
            }
            
        } catch (\Exception $e) {
            // مدیریت خطا و مقادیر پیش‌فرض
            $this->handleStatisticsError($e, $traceId, $startTime);
        }
    }

    /**
     * مدیریت خطاهای آماری و تنظیم مقادیر پیش‌فرض
     */
    private function handleStatisticsError(\Exception $e, string $traceId = null, float $startTime = null)
    {
        $traceId = $traceId ?: uniqid('ERROR_', true);
        $executionTime = $startTime ? round((microtime(true) - $startTime) * 1000, 2) . 'ms' : 'N/A';
        
        Log::error("[{$traceId}] ❌ Dashboard statistics loading failed", [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode(),
            'error_file' => $e->getFile(),
            'error_line' => $e->getLine(),
            'panel_type' => $this->panelType,
            'user_id' => auth()->id(),
            'execution_time' => $executionTime,
            'stack_trace' => $e->getTraceAsString()
        ]);
        
        // تنظیم مقادیر پیش‌فرض مشترک
        $this->totalInsured = 0;
        $this->maleCount = 0;
        $this->femaleCount = 0;
        $this->totalOrganizations = 0;
        
        if ($this->panelType === 'charity') {
            // مقادیر پیش‌فرض خیریه
            $this->insuredFamilies = 0;
            $this->uninsuredFamilies = 0;
            $this->insuredMembers = 0;
            $this->uninsuredMembers = 0;
            $this->totalFamilies = 0;
            $this->totalDeprived = 0;
            $this->pendingFamilies = 0;
        } else {
            // مقادیر پیش‌فرض بیمه
            $this->totalPayment = 0;
        }
        
        // نمایش پیام خطا به کاربر (اختیاری)
        session()->flash('error', 'خطا در بارگذاری آمار داشبورد. لطفاً دوباره تلاش کنید.');
    }

    /**
     * محاسبه آمار اصلی
     */
    private function calculateStatistics()
    {
        if ($this->panelType === 'charity') {
            return $this->calculateCharityStatistics();
        }
        
        return $this->calculateInsuranceStatistics();
    }

    /**
     * محاسبه آمار خیریه
     */
    private function calculateCharityStatistics()
    {
        try {
            $charityId = Auth::user()->organization_id;
            $orgFilter = $this->selectedOrganization ?: $charityId;
            
            // آمار خانواده‌های بیمه‌شده
            $insuredFamilies = Family::where('charity_id', $orgFilter)
                ->where(function($q) {
                    $q->whereHas('insurances')
                      ->orWhere('is_insured', true)
                      ->orWhere('is_insured', 1);
                })->count();

            // آمار خانواده‌های بیمه نشده
            $uninsuredFamilies = Family::where('charity_id', $orgFilter)
                ->whereDoesntHave('insurances')
                ->where(function($q) {
                    $q->where('is_insured', false)
                      ->orWhere('is_insured', 0)
                      ->orWhereNull('is_insured');
                })->count();

            // آمار اعضای بیمه‌شده
            $insuredMembers = Member::whereHas('family', function($q) use ($orgFilter) {
                $q->where('charity_id', $orgFilter)
                  ->where(function($subq) {
                      $subq->whereHas('insurances')
                           ->orWhere('is_insured', true)
                           ->orWhere('is_insured', 1);
                  });
            })->count();

            // آمار اعضای بیمه نشده
            $uninsuredMembers = Member::whereHas('family', function($q) use ($orgFilter) {
                $q->where('charity_id', $orgFilter)
                  ->whereDoesntHave('insurances')
                  ->where(function($subq) {
                      $subq->where('is_insured', false)
                           ->orWhere('is_insured', 0)
                           ->orWhereNull('is_insured');
                  });
            })->count();

            // آمار جنسیتی
            $maleCount = Member::whereHas('family', function($q) use ($orgFilter) {
                $q->where('charity_id', $orgFilter);
            })->where('gender', 'male')->count();
            
            $femaleCount = Member::whereHas('family', function($q) use ($orgFilter) {
                $q->where('charity_id', $orgFilter);
            })->where('gender', 'female')->count();

            // تعداد سازمان‌های فعال
            $totalOrganizations = Organization::active()->count();

            // کل خانواده‌های ثبت شده
            $totalFamilies = Family::where('charity_id', $orgFilter)->count();

            // افراد محروم (بر اساس معیارهای محرومیت)
            $totalDeprived = Member::whereHas('family', function($q) use ($orgFilter) {
                $q->where('charity_id', $orgFilter)
                  ->where(function($subq) {
                      $subq->where('is_deprived', true)
                           ->orWhere('is_deprived', 1)
                           ->orWhere('deprivation_score', '>', 0);
                  });
            })->count();

            // خانواده‌های در انتظار تایید (وضعیت pending)
            $pendingFamilies = Family::where('charity_id', $orgFilter)
                ->where(function($q) {
                    $q->where('status', 'pending')
                      ->orWhere('approval_status', 'pending')
                      ->orWhereNull('approval_status');
                })->count();

            return [
                'insuredFamilies' => $insuredFamilies,
                'uninsuredFamilies' => $uninsuredFamilies,
                'insuredMembers' => $insuredMembers,
                'uninsuredMembers' => $uninsuredMembers,
                'maleCount' => $maleCount,
                'femaleCount' => $femaleCount,
                'totalOrganizations' => $totalOrganizations,
                'totalInsured' => $insuredMembers,
                'totalPayment' => 0, // خیریه پرداخت مستقیم ندارد
                'totalFamilies' => $totalFamilies,
                'totalDeprived' => $totalDeprived,
                'pendingFamilies' => $pendingFamilies,
            ];

        } catch (\Exception $e) {
            \Log::error('خطا در محاسبه آمار خیریه: ' . $e->getMessage());
            
            return [
                'insuredFamilies' => 0,
                'uninsuredFamilies' => 0,
                'insuredMembers' => 0,
                'uninsuredMembers' => 0,
                'maleCount' => 0,
                'femaleCount' => 0,
                'totalOrganizations' => 0,
                'totalInsured' => 0,
                'totalPayment' => 0,
                'totalFamilies' => 0,
                'totalDeprived' => 0,
                'pendingFamilies' => 0,
            ];
        }
    }

    /**
     * محاسبه آمار بیمه
     */
    private function calculateInsuranceStatistics()
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
     * محاسبه پرداخت‌های کل (فقط برای پنل بیمه)
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

        // ساخت کلید کش منحصر به فرد بر اساس فیلترها
        $cacheKey = "geo_data_{$this->selectedYear}_{$this->selectedMonth}_{$this->selectedOrganization}";

        // ذخیره نتایج در کش به مدت 6 ساعت
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($dateRange) {
            // استفاده از Eloquent برای کوئری بهتر
            $query = Province::query()
                ->leftJoin('families', 'provinces.id', '=', 'families.province_id')
                ->leftJoin('members', 'families.id', '=', 'members.family_id')
                ->leftJoin('family_insurances', 'families.id', '=', 'family_insurances.family_id');

            // انتخاب فقط فیلدهای مورد نیاز برای بهبود عملکرد
            $query->select(
                'provinces.id',
                'provinces.name as province_name',
                DB::raw('COUNT(DISTINCT CASE WHEN members.gender = "male" THEN members.id END) as male_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN members.gender = "female" THEN members.id END) as female_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN families.poverty_confirmed = 1 THEN members.id END) as deprived_count')
            );

            // فیلتر زمانی - بر اساس تاریخ صدور بیمه
            $query->whereBetween('family_insurances.start_date', [$dateRange['start'], $dateRange['end']]);

            // فیلتر سازمان
            if ($this->selectedOrganization) {
                $query->where(function($q) {
                    $q->where('families.charity_id', $this->selectedOrganization)
                      ->orWhere('families.insurance_id', $this->selectedOrganization);
                });
            }

            // اضافه کردن ایندکس به کوئری
            $query->whereNotNull('members.id') // فقط استان‌هایی که عضو دارند
                  ->groupBy('provinces.id', 'provinces.name')
                  ->orderBy('provinces.name');

            // اجرای کوئری
            $results = $query->get();

            // آماده‌سازی نتایج برای chart
            return [
                'provinceNames' => $results->pluck('province_name')->toArray(),
                'provinceMaleCounts' => $results->pluck('male_count')->map(fn($v) => (int)$v)->toArray(),
                'provinceFemaleCounts' => $results->pluck('female_count')->map(fn($v) => (int)$v)->toArray(),
                'provinceDeprivedCounts' => $results->pluck('deprived_count')->map(fn($v) => (int)$v)->toArray()
            ];
        });
    }

    /**
     * داده‌های مالی کلی (فقط برای پنل بیمه)
     */
    private function getFinancialData()
    {
        // ساخت کلید کش منحصر به فرد بر اساس فیلترها
        $cacheKey = "financial_data_{$this->selectedYear}_{$this->selectedMonth}_{$this->selectedOrganization}";

        return Cache::remember($cacheKey, now()->addHours(6), function () {
            $dateRange = $this->getDateRange();

            try {
                DB::enableQueryLog(); // فعال‌سازی لاگ کوئری برای بررسی عملکرد

                // استفاده از یک کوئری با select برای بهبود عملکرد
                $transactionSum = FundingTransaction::query()
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                    ->select(DB::raw('SUM(amount) as total_amount'))
                    ->first();

                // محاسبه داده‌ها با یک کوئری بهینه‌تر
                $totalTransactions = $transactionSum->total_amount ?? 0;

                // بهینه‌سازی کوئری برای خسارات پرداخت شده
                $paidClaimsSum = InsuranceAllocation::query()
                    ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                    ->where('status', 'paid')
                    ->select(DB::raw('SUM(amount) as total_paid'))
                    ->first();

                $paidClaims = $paidClaimsSum->total_paid ?? 0;

                // محاسبه بودجه‌ی پیش‌فرض - حدود ۱۵٪ بالاتر از مجموع پرداخت‌ها
                $budgetAmount = $paidClaims > 0 ? $paidClaims * 1.15 : $totalTransactions;

                // تبدیل اعداد به فرمت مناسب برای نمایش
                $displayFormat = function($value) {
                    return number_format($value / 1000000, 1);
                };

                $totalDisplay = $displayFormat($totalTransactions);
                $premiumsDisplay = $displayFormat($totalTransactions);
                $claimsDisplay = $displayFormat($paidClaims);
                $budgetDisplay = $displayFormat($budgetAmount);

                $premiumsPercentage = 0;
                $claimsPercentage = 0;

                if ($budgetAmount > 0) {
                    $premiumsPercentage = round(($totalTransactions / $budgetAmount) * 100);
                    $claimsPercentage = round(($paidClaims / $budgetAmount) * 100);
                }

                // غیرفعال‌سازی لاگ پس از اتمام
                DB::disableQueryLog();

                return [
                    'premiums' => $totalTransactions,
                    'claims' => $paidClaims,
                    'total' => $totalTransactions,
                    'budget' => $budgetAmount,
                    'premiumsDisplay' => $premiumsDisplay,
                    'claimsDisplay' => $claimsDisplay,
                    'totalDisplay' => $totalDisplay,
                    'budgetDisplay' => $budgetDisplay,
                    'unit' => 'میلیون تومان',
                    'premiumsPercentage' => $premiumsPercentage,
                    'claimsPercentage' => $claimsPercentage
                ];
            } catch (\Exception $e) {
                Log::error('Error in financial data calculation', ['error' => $e->getMessage()]);

                return [
                    'premiums' => 0,
                    'claims' => 0,
                    'total' => 0,
                    'budget' => 0,
                    'premiumsDisplay' => '0',
                    'claimsDisplay' => '0',
                    'totalDisplay' => '0',
                    'budgetDisplay' => '0',
                    'unit' => 'میلیون تومان',
                    'premiumsPercentage' => 0,
                    'claimsPercentage' => 0
                ];
            }
        });
    }

    /**
     * جریان مالی ماهانه (فقط برای پنل بیمه)
     */
    private function getMonthlyFinancialFlow()
    {
        // ساخت کلید کش منحصر به فرد بر اساس فیلترها
        $cacheKey = "monthly_flow_{$this->selectedYear}_{$this->selectedMonth}_{$this->selectedOrganization}";

        // ذخیره نتایج در کش به مدت 6 ساعت
        return Cache::remember($cacheKey, now()->addHours(6), function () {
            $result = [];

            try {
                // اگر ماه خاص انتخاب شده، فقط همان ماه را نمایش می‌دهیم
                $monthsToShow = $this->selectedMonth ? [$this->selectedMonth] : range(1, 12);

                foreach ($monthsToShow as $month) {
                    $dateRange = $this->convertJalaliToGregorian($this->selectedYear, $month);

                    // بهینه‌سازی کوئری‌ها با استفاده از select
                    $premiums = FamilyInsurance::query()
                        ->select(DB::raw('SUM(premium_amount) as premium_sum'))
                        ->whereBetween('start_date', [$dateRange['start'], $dateRange['end']])
                        ->when($this->selectedOrganization, function($q) {
                            return $q->whereHas('family', function($family) {
                                $family->where('charity_id', $this->selectedOrganization)
                                      ->orWhere('insurance_id', $this->selectedOrganization);
                            });
                        })
                        ->first();

                    $claims = InsuranceAllocation::query()
                        ->select(DB::raw('SUM(amount) as claims_sum'))
                        ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                        ->when($this->selectedOrganization, function($q) {
                            return $q->whereHas('family', function($family) {
                                $family->where('charity_id', $this->selectedOrganization)
                                      ->orWhere('insurance_id', $this->selectedOrganization);
                            });
                        })
                        ->first();

                    $budget = FundingTransaction::query()
                        ->select(DB::raw('SUM(amount) as budget_sum'))
                        ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                        ->first();

                    // استخراج مقادیر از نتایج کوئری با مقدار پیش‌فرض 0
                    $premiumAmount = (int)($premiums->premium_sum ?? 0);
                    $claimsAmount = (int)($claims->claims_sum ?? 0);
                    $budgetAmount = (int)($budget->budget_sum ?? 0);

                    $result[] = [
                        'month' => $month,
                        'monthName' => $this->getJalaliMonths()[$month],
                        'premiums' => $premiumAmount,
                        'claims' => $claimsAmount,
                        'budget' => $budgetAmount,
                        'total' => $premiumAmount + $claimsAmount + $budgetAmount
                    ];
                }

                return $result;

            } catch (\Exception $e) {
                // در صورت خطا، لاگ خطا را ثبت کرده و آرایه خالی برگردان
                Log::error('Error in monthly financial flow calculation', ['error' => $e->getMessage()]);
                return [];
            }
        });
    }

    /**
     * تحلیل معیارهای پذیرش بهینه‌شده
     */
    private function getOptimizedCriteriaAnalysis()
    {
        // ساخت کلید کش منحصر به فرد بر اساس فیلترها
        $cacheKey = "criteria_data_{$this->selectedYear}_{$this->selectedMonth}_{$this->selectedOrganization}";

        return Cache::remember($cacheKey, now()->addHours(6), function () {
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
            $deprivedFamiliesQuery = (clone $familiesQuery)->where('families.poverty_confirmed', 1);
            $deprivedFamilies = $deprivedFamiliesQuery->count();

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

            // محاسبه آمار خاص اعضا
            $disabilityCount = (clone $membersQuery)->where('members.has_disability', 1)->distinct('members.id')->count('members.id');
            $chronicCount = (clone $membersQuery)->where('members.has_chronic_disease', 1)->distinct('members.id')->count('members.id');

            // مقادیر ایمن برای جلوگیری از تقسیم بر صفر
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
        });
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
        try {
            Log::info('📅 Filter month changed', [
                'month' => $this->selectedMonth,
                'year' => $this->selectedYear,
                'organization' => $this->selectedOrganization,
                'user_id' => auth()->id()
            ]);
            
            $this->clearCache();
            $this->loadStatistics();
            
            // اجبار به refresh کامل کامپوننت
            $this->dispatch('refreshDashboard');
            $this->dispatch('refreshAllCharts');
            
            // نمایش پیام موفقیت
            $this->dispatch('showToast', [
                'message' => 'فیلتر ماه با موفقیت اعمال شد',
                'type' => 'success'
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error updating month filter', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }

    public function updatedSelectedYear()
    {
        try {
            Log::info('📅 Filter year changed', [
                'year' => $this->selectedYear,
                'month' => $this->selectedMonth,
                'organization' => $this->selectedOrganization,
                'user_id' => auth()->id()
            ]);
            
            $this->clearCache();
            $this->loadStatistics();
            
            // اجبار به refresh کامل کامپوننت
            $this->dispatch('refreshDashboard');
            $this->dispatch('refreshAllCharts');
            
            // نمایش پیام موفقیت
            $this->dispatch('showToast', [
                'message' => 'فیلتر سال با موفقیت اعمال شد',
                'type' => 'success'
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error updating year filter', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }

    public function updatedSelectedOrganization()
    {
        try {
            Log::info('🏢 Filter organization changed', [
                'organization' => $this->selectedOrganization,
                'year' => $this->selectedYear,
                'month' => $this->selectedMonth,
                'user_id' => auth()->id()
            ]);
            
            $this->clearCache();
            $this->loadStatistics();
            
            // اجبار به refresh کامل کامپوننت
            $this->dispatch('refreshDashboard');
            $this->dispatch('refreshAllCharts');
            
            // نمایش پیام موفقیت
            $this->dispatch('showToast', [
                'message' => 'فیلتر سازمان با موفقیت اعمال شد',
                'type' => 'success'
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error updating organization filter', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
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
        try {
            // کلیدهای کش برای تنظیمات فعلی
            $currentKeys = [
                $this->getCacheKey(),
                "geo_data_{$this->selectedYear}_{$this->selectedMonth}_{$this->selectedOrganization}",
                "financial_data_{$this->selectedYear}_{$this->selectedMonth}_{$this->selectedOrganization}",
                "criteria_data_{$this->selectedYear}_{$this->selectedMonth}_{$this->selectedOrganization}"
            ];

            // کلیدهای کش برای تمام احتمالات (برای اطمینان)
            $allPossibleKeys = [];
            
            // پاک کردن کش برای تمام سال‌ها و ماه‌ها
            foreach ($this->jalaliYears as $year) {
                // کل سال
                $allPossibleKeys[] = "dashboard_stats_{$year}__{$this->selectedOrganization}";
                $allPossibleKeys[] = "geo_data_{$year}__{$this->selectedOrganization}";
                $allPossibleKeys[] = "financial_data_{$year}__{$this->selectedOrganization}";
                $allPossibleKeys[] = "criteria_data_{$year}__{$this->selectedOrganization}";
                
                // هر ماه
                for ($month = 1; $month <= 12; $month++) {
                    $allPossibleKeys[] = "dashboard_stats_{$year}_{$month}_{$this->selectedOrganization}";
                    $allPossibleKeys[] = "geo_data_{$year}_{$month}_{$this->selectedOrganization}";
                    $allPossibleKeys[] = "financial_data_{$year}_{$month}_{$this->selectedOrganization}";
                    $allPossibleKeys[] = "criteria_data_{$year}_{$month}_{$this->selectedOrganization}";
                }
            }

            // ترکیب همه کلیدها
            $allKeys = array_merge($currentKeys, $allPossibleKeys);
            $allKeys = array_unique($allKeys);

            $clearedCount = 0;
            foreach ($allKeys as $key) {
                if (Cache::forget($key)) {
                    $clearedCount++;
                }
            }

            Log::info('🗑️ Cache cleared', [
                'keys_cleared' => $clearedCount,
                'total_keys' => count($allKeys),
                'current_filters' => [
                    'year' => $this->selectedYear,
                    'month' => $this->selectedMonth,
                    'organization' => $this->selectedOrganization
                ],
                'user_id' => auth()->id()
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Error clearing cache', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
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
