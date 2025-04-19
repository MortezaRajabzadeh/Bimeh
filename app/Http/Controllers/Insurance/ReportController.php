<?php

namespace App\Http\Controllers\Insurance;

use App\Exports\FamiliesExport;
use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Region;
use App\Services\FamilyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    protected FamilyService $familyService;
    
    public function __construct(FamilyService $familyService)
    {
        $this->familyService = $familyService;
    }
    
    /**
     * نمایش صفحه گزارش‌ها
     */
    public function index(Request $request)
    {
        Gate::authorize('view reports');
        
        $regions = Region::active()->get();
        $insuranceCompany = $request->user()->organization;
        
        // فیلترهای پیش‌فرض برای کاربر بیمه (فقط خانواده‌های مربوط به سازمان خودش)
        $filters = $request->only(['region_id', 'status', 'date_from', 'date_to']);
        $filters['insurance_id'] = $insuranceCompany ? $insuranceCompany->id : null;
        
        // جمع آمار
        $statistics = [
            'total' => Family::where('insurance_id', $filters['insurance_id'])->count(),
            'approved' => Family::where('insurance_id', $filters['insurance_id'])->approved()->count(),
            'pending' => Family::where('insurance_id', $filters['insurance_id'])->pending()->count(),
            'reviewing' => Family::where('insurance_id', $filters['insurance_id'])->reviewing()->count(),
            'rejected' => Family::where('insurance_id', $filters['insurance_id'])->rejected()->count(),
        ];
        
        // دریافت لیست خانواده‌ها با فیلترها
        $families = null;
        if ($request->filled('filter')) {
            $term = $request->input('term', '');
            $families = $this->familyService->searchFamilies($term, $filters, 15);
        }
        
        return view('insurance.reports.index', compact(
            'regions',
            'insuranceCompany',
            'filters',
            'statistics',
            'families'
        ));
    }
    
    /**
     * صدور گزارش اکسل
     */
    public function export(Request $request)
    {
        Gate::authorize('export reports');
        
        $filters = $request->only(['region_id', 'status', 'date_from', 'date_to']);
        $filters['insurance_id'] = $request->user()->organization_id;
        
        return Excel::download(
            new FamiliesExport($filters),
            'families_report_' . date('Y-m-d') . '.xlsx'
        );
    }
} 