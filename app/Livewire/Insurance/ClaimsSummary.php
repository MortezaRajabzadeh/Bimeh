<?php

namespace App\Livewire\Insurance;

use Livewire\Component;
use App\Models\ClaimsSummary as ClaimsSummaryModel;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClaimsSummary extends Component
{
    use WithPagination;

    public $viewType = 'summary'; // summary, monthly, top_families, by_insurance_type, by_status
    public $startDate = '';
    public $endDate = '';
    public $selectedYear;
    public $selectedInsuranceType = '';
    public $familyCode = '';
    public $paymentStatus = '';
    public $minAmount = null;
    public $maxAmount = null;
    public $perPage = 25;
    public $showFilters = true; // نمایش فیلترها به صورت پیش فرض

    protected $queryString = [
        'viewType',
        'startDate',
        'endDate', 
        'selectedYear',
        'selectedInsuranceType',
        'familyCode',
        'paymentStatus',
        'minAmount',
        'maxAmount',
        'perPage'
    ];

    public function mount()
    {
        // تنظیم سال پیش‌فرض فقط
        $this->selectedYear = (int) jdate()->format('Y');
        
        // فیلدهای تاریخ خالی می‌مانند تا کاربر خودش انتخاب کند
    }

    public function updatedViewType()
    {
        $this->resetPage();
        $this->clearCache();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
        $this->clearCache();
        $this->dispatch('date-selected');
    }

    public function updatedEndDate()
    {
        $this->resetPage();
        $this->clearCache();
        $this->dispatch('date-selected');
    }

    public function updatedSelectedYear()
    {
        $this->resetPage();
        $this->clearCache();
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function applyFilters()
    {
        $this->resetPage();
        $this->clearCache();
        $this->showFilters = false;
        
        session()->flash('success', 'فیلترها اعمال شد');
    }

    public function clearFilters()
    {
        $this->startDate = jdate()->format('Y/m/01');
        $this->endDate = jdate()->format('Y/m/t');
        $this->selectedYear = (int) jdate()->format('Y');
        $this->selectedInsuranceType = '';
        $this->familyCode = '';
        $this->paymentStatus = '';
        $this->minAmount = null;
        $this->maxAmount = null;
        $this->perPage = 25;
        $this->resetPage();
        $this->clearCache();
        
        session()->flash('success', 'فیلترها پاک شد');
    }

    private function clearCache()
    {
        // پاک کردن کش‌های مرتبط بدون استفاده از tagging
        $keys = [
            "claims_summary_summary_{$this->startDate}_{$this->endDate}_{$this->selectedYear}_{$this->selectedInsuranceType}",
            "claims_summary_monthly_{$this->startDate}_{$this->endDate}_{$this->selectedYear}_{$this->selectedInsuranceType}",
            "claims_summary_top_families_{$this->startDate}_{$this->endDate}_{$this->selectedYear}_{$this->selectedInsuranceType}",
            'claims_overall_stats',
            'available_insurance_types'
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    public function getSummaryDataProperty()
    {
        $cacheKey = "claims_summary_{$this->viewType}_{$this->startDate}_{$this->endDate}_{$this->selectedYear}_{$this->selectedInsuranceType}_{$this->familyCode}_{$this->paymentStatus}_{$this->minAmount}_{$this->maxAmount}";
        
        return Cache::remember($cacheKey, 3600, function () {
            $filters = [
                'familyCode' => $this->familyCode,
                'paymentStatus' => $this->paymentStatus,
                'minAmount' => $this->minAmount,
                'maxAmount' => $this->maxAmount
            ];
            
            switch ($this->viewType) {
                case 'monthly':
                    return ClaimsSummaryModel::getMonthlySummary($this->selectedYear, $this->selectedInsuranceType, $filters);
                
                case 'top_families':
                    return ClaimsSummaryModel::getTopFamiliesByClaims(20, $this->startDate, $this->endDate, $this->selectedInsuranceType, $filters);
                
                case 'by_insurance_type':
                    return ClaimsSummaryModel::getSummaryByInsuranceType($this->startDate, $this->endDate, $filters);
                
                case 'by_status':
                    return ClaimsSummaryModel::getSummaryByStatus($this->startDate, $this->endDate, $this->selectedInsuranceType, $filters);
                
                case 'summary':
                default:
                    return ClaimsSummaryModel::getSummaryByDateAndType($this->startDate, $this->endDate, $this->selectedInsuranceType, $filters);
            }
        });
    }

    public function getOverallStatsProperty()
    {
        $cacheKey = "claims_overall_stats_{$this->startDate}_{$this->endDate}_{$this->selectedInsuranceType}";
        return Cache::remember($cacheKey, 3600, function () {
            return ClaimsSummaryModel::getOverallStats($this->startDate, $this->endDate, $this->selectedInsuranceType);
        });
    }

    public function getAvailableInsuranceTypesProperty()
    {
        return Cache::remember('available_insurance_types', 3600, function () {
            return DB::table('funding_transactions')
                ->select('description')
                ->whereNotNull('description')
                ->where('description', '!=', '')
                ->distinct()
                ->orderBy('description')
                ->pluck('description')
                ->toArray();
        });
    }

    // Export methods
    public function exportExcel()
    {
        session()->flash('success', 'خروجی Excel در حال آماده‌سازی است...');
        // TODO: Implement Excel export functionality
    }
    
    public function exportPdf()
    {
        session()->flash('success', 'خروجی PDF در حال آماده‌سازی است...');
        // TODO: Implement PDF export functionality
    }
    
    public function printReport()
    {
        $this->dispatch('print-report');
    }
    
    public function render()
    {
        // اگر perPage = 0 باشد، همه رکوردها را نمایش بده
        $data = $this->perPage == 0 ? $this->summaryData : $this->summaryData->take($this->perPage);
        
        return view('livewire.insurance.claims-summary', [
            'summaryData' => $data,
            'overallStats' => $this->overallStats,
            'availableInsuranceTypes' => $this->availableInsuranceTypes,
        ]);
    }
}
