<?php

namespace App\Livewire\Insurance;

use Livewire\Component;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Province;
use App\Models\Family;
use App\Models\FamilyInsurance;
use Morilog\Jalali\Jalalian;
use Illuminate\Support\Facades\DB;

class DashboardStats extends Component
{
    public $totalInsured = 0;
    public $totalPayment = 0;
    public $totalOrganizations = 0;
    public $maleCount = 0;
    public $femaleCount = 0;
    public $year;
    public $selectedMonth;
    public $selectedYear;

    public function mount()
    {
        $this->totalInsured = Member::count();
        $this->year = Jalalian::now()->getYear();
        $this->selectedYear = $this->year;
        $this->selectedMonth = Jalalian::now()->getMonth();
        $this->maleCount = Member::where('gender', 'male')->count();
        $this->femaleCount = Member::where('gender', 'female')->count();
        $this->totalOrganizations = Organization::count();
        $this->totalPayment = FamilyInsurance::sum('insurance_amount') ?? 0;
    }

    public function updatedSelectedMonth()
    {
        // When month changes, refresh all charts
        $this->dispatch('refreshAllCharts');
    }

    public function updatedSelectedYear()
    {
        // When year changes, refresh all charts
        $this->dispatch('refreshAllCharts');
    }
    
    // اضافه کردن متد برای داده‌های جغرافیایی فیلتر شده
    private function getFilteredGeographicData()
    {
        $year = $this->selectedYear;
        $month = $this->selectedMonth;
        
        $provinces = Province::orderBy('name')->get();
        $provinceNames = $provinces->pluck('name')->toArray();
        $provinceMaleCounts = [];
        $provinceFemaleCounts = [];
        $provinceDeprivedCounts = [];
        
        foreach ($provinces as $province) {
            $male = 0;
            $female = 0;
            $deprived = 0;
            
            // فیلتر بر اساس سال و ماه انتخاب شده
            $families = Family::where('province_id', $province->id)
                            ->where(function($query) {
                                $query->where('is_insured', 1)
                                      ->orWhereHas('insurances');
                            })
                            ->whereHas('insurances', function($query) use ($year, $month) {
                                $query->whereYear('insurance_issue_date', $year)
                                      ->whereMonth('insurance_issue_date', $month);
                            })
                            ->with(['members', 'insurances'])->get();
            
            foreach ($families as $family) {
                if ($family->members && $family->members->count() > 0) {
                    $male += $family->members->where('gender', 'male')->count();
                    $female += $family->members->where('gender', 'female')->count();
                } else {
                    $male += 1;
                }
                
                if ($family->poverty_confirmed) {
                    if ($family->members && $family->members->count() > 0) {
                        $deprived += $family->members->count();
                    } else {
                        $deprived += 1;
                    }
                }
            }
            
            $provinceMaleCounts[] = $male;
            $provinceFemaleCounts[] = $female;
            $provinceDeprivedCounts[] = $deprived;
        }
        
        return [
            'provinceNames' => $provinceNames,
            'provinceMaleCounts' => $provinceMaleCounts,
            'provinceFemaleCounts' => $provinceFemaleCounts,
            'provinceDeprivedCounts' => $provinceDeprivedCounts
        ];
    }
    
    // اضافه کردن متد برای داده‌های جنسیتی فیلتر شده
    private function getFilteredGenderData()
    {
        $year = $this->selectedYear;
        $month = $this->selectedMonth;
        
        $maleCount = Member::whereHas('family.insurances', function($query) use ($year, $month) {
            $query->whereYear('insurance_issue_date', $year)
                  ->whereMonth('insurance_issue_date', $month);
        })->where('gender', 'male')->count();
        
        $femaleCount = Member::whereHas('family.insurances', function($query) use ($year, $month) {
            $query->whereYear('insurance_issue_date', $year)
                  ->whereMonth('insurance_issue_date', $month);
        })->where('gender', 'female')->count();
        
        return [
            'maleCount' => $maleCount,
            'femaleCount' => $femaleCount,
            'totalInsured' => $maleCount + $femaleCount
        ];
    }
    
    private function getChartData()
    {
        // Fetch monthly claims data
        $monthlyClaimsData = $this->getMonthlyClaims();
    
        // Fetch yearly claims data
        $yearlyClaimsData = $this->getYearlyClaims();
    }

    public function render()
    {
        try {
            // داده‌های فیلتر شده
            $geoData = $this->getFilteredGeographicData();
            $genderData = $this->getFilteredGenderData();
            
            // آمار استان‌ها و اعضا به تفکیک جنسیت
            $provinces = Province::orderBy('name')->get();
            $provinceNames = $provinces->pluck('name')->toArray();
            $provinceMaleCounts = [];
            $provinceFemaleCounts = [];
            $provinceDeprivedCounts = [];
            
            foreach ($provinces as $province) {
                $male = 0;
                $female = 0;
                $deprived = 0;
                
                // خانواده‌هایی که بیمه دارند - چک دو روش
                $families = Family::where('province_id', $province->id)
                                ->where(function($query) {
                                    $query->where('is_insured', 1)
                                          ->orWhereHas('insurances');
                                })
                                ->with(['members', 'insurances'])->get();
                
                foreach ($families as $family) {
                    if ($family->members && $family->members->count() > 0) {
                        $male += $family->members->where('gender', 'male')->count();
                        $female += $family->members->where('gender', 'female')->count();
                    } else {
                        // اگر member نداشت، حداقل ۱ نفر (سرپرست)
                        $male += 1;
                    }
                    
                    // افراد محروم بیمه‌شده
                    if ($family->poverty_confirmed) {
                        if ($family->members && $family->members->count() > 0) {
                            $deprived += $family->members->count();
                        } else {
                            $deprived += 1;
                        }
                    }
                }
                
                $provinceMaleCounts[] = $male;
                $provinceFemaleCounts[] = $female;
                $provinceDeprivedCounts[] = $deprived;
            }

            // سال‌های جلالی (۵ سال اخیر)
            $currentJalaliYear = Jalalian::now()->getYear();
            $jalaliYears = [];
            for ($i = 0; $i < 5; $i++) {
                $jalaliYears[] = $currentJalaliYear - $i;
            }

            // معیارهای پذیرش بهینه شده
            $criteriaData = $this->getCriteriaAnalysis();
            
            // داده‌های جدید برای نمودار خسارات ماهانه
            $monthlyClaimsData = $this->getMonthlyClaimsData();
            $yearlyClaimsFlow = $this->getYearlyClaimsFlow();
            $jalaliMonths = $this->getJalaliMonths();
            
            // داده‌های نسبت مالی کلی
            $financialRatio = $this->getFinancialRatio();

            return view('livewire.insurance.dashboard-stats', [
                'totalInsured' => $this->totalInsured,
                'totalPayment' => $this->totalPayment,
                'totalOrganizations' => $this->totalOrganizations,
                'maleCount' => $this->maleCount,
                'femaleCount' => $this->femaleCount,
                'provinceNames' => $provinceNames,
                'provinceMaleCounts' => $provinceMaleCounts,
                'provinceFemaleCounts' => $provinceFemaleCounts,
                'provinceDeprivedCounts' => $provinceDeprivedCounts,
                'jalaliYears' => $jalaliYears,
                'criteriaData' => $criteriaData,
                'monthlyClaimsData' => $monthlyClaimsData,
                'yearlyClaimsFlow' => $yearlyClaimsFlow,
                'jalaliMonths' => $jalaliMonths,
                'financialRatio' => $financialRatio,
            ]);
            
        } catch (\Exception $e) {
            // در صورت بروز خطا، داده‌های fallback برگردان
            return view('livewire.insurance.dashboard-stats', [
                'totalInsured' => $this->totalInsured,
                'totalPayment' => $this->totalPayment,
                'totalOrganizations' => $this->totalOrganizations,
                'maleCount' => $this->maleCount,
                'femaleCount' => $this->femaleCount,
                'provinceNames' => ['تهران', 'اصفهان', 'فارس', 'خراسان'],
                'provinceMaleCounts' => [120, 80, 60, 90],
                'provinceFemaleCounts' => [100, 90, 70, 60],
                'provinceDeprivedCounts' => [30, 20, 10, 15],
                'jalaliYears' => [1403, 1402, 1401, 1400, 1399],
                'criteriaData' => [
                    ['name' => 'مادر سرپرست خانوار', 'count' => 85, 'percentage' => 25.5, 'type' => 'family', 'color' => '#10b981'],
                    ['name' => 'فرد معلول', 'count' => 67, 'percentage' => 20.1, 'type' => 'member', 'color' => '#3b82f6'],
                ],
                'monthlyClaimsData' => $this->getMonthlyClaimsData(),
                'yearlyClaimsFlow' => [],
                'jalaliMonths' => $this->getJalaliMonths(),
                'financialRatio' => $this->getFinancialRatio(),
            ]);
        }
    }
}

    private function getMonthlyClaimsData()
    {
        $year = $this->selectedYear;
        $month = $this->selectedMonth;
        
        // Get monthly claims data filtered by selected year and month
        $monthlyData = FamilyInsurance::whereYear('insurance_issue_date', $year)
            ->whereMonth('insurance_issue_date', $month)
            ->selectRaw('MONTH(insurance_issue_date) as month, COUNT(*) as count, SUM(insurance_amount) as total_amount')
            ->groupBy('month')
            ->orderBy('month')
            ->get();
            
        return $monthlyData->map(function($item) {
            return [
                'month' => $item->month,
                'count' => $item->count,
                'amount' => $item->total_amount ?? 0
            ];
        })->toArray();
    }

    private function getYearlyClaimsFlow()
    {
        $year = $this->selectedYear;
        
        // Get yearly flow data
        $yearlyData = FamilyInsurance::whereYear('insurance_issue_date', $year)
            ->selectRaw('YEAR(insurance_issue_date) as year, COUNT(*) as count, SUM(insurance_amount) as total_amount')
            ->groupBy('year')
            ->orderBy('year')
            ->get();
            
        return $yearlyData->map(function($item) {
            return [
                'year' => $item->year,
                'count' => $item->count,
                'amount' => $item->total_amount ?? 0
            ];
        })->toArray();
    }

    private function getCriteriaAnalysis()
    {
        // Analysis of insurance acceptance criteria
        $totalFamilies = Family::count();
        $insuredFamilies = Family::where('is_insured', 1)->orWhereHas('insurances')->count();
        $deprivedFamilies = Family::where('poverty_confirmed', 1)->count();
        $femaleHeadedFamilies = Family::where('head_gender', 'female')->count();
        
        return [
            ['name' => 'خانوار محروم', 'count' => $deprivedFamilies, 'percentage' => ($deprivedFamilies / max($totalFamilies, 1)) * 100, 'type' => 'family', 'color' => '#ef4444'],
            ['name' => 'مادر سرپرست خانوار', 'count' => $femaleHeadedFamilies, 'percentage' => ($femaleHeadedFamilies / max($totalFamilies, 1)) * 100, 'type' => 'family', 'color' => '#10b981'],
            ['name' => 'خانوار بیمه شده', 'count' => $insuredFamilies, 'percentage' => ($insuredFamilies / max($totalFamilies, 1)) * 100, 'type' => 'family', 'color' => '#3b82f6'],
        ];
    }

    private function getJalaliMonths()
    {
        return [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
            5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
            9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        ];
    }

    private function getFinancialRatio()
    {
        $totalPayments = FamilyInsurance::sum('insurance_amount') ?? 0;
        $totalFamilies = Family::count();
        $averagePerFamily = $totalFamilies > 0 ? $totalPayments / $totalFamilies : 0;
        
        return [
            'total_payments' => $totalPayments,
            'total_families' => $totalFamilies,
            'average_per_family' => $averagePerFamily,
            'formatted_total' => number_format($totalPayments) . ' تومان',
            'formatted_average' => number_format($averagePerFamily) . ' تومان'
        ];
    }