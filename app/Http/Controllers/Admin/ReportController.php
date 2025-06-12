<?php

namespace App\Http\Controllers\Admin;

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
        $charities = Organization::charity()->active()->get();
        $insuranceCompanies = Organization::insurance()->active()->get();
        
        $filters = $request->only(['charity_id', 'insurance_id', 'region_id', 'status', 'date_from', 'date_to']);
        
        // جمع آمار
        $statistics = $this->familyService->getStatistics();
        
        // دریافت لیست خانواده‌ها با فیلترها
        $families = null;
        if ($request->filled('filter')) {
            $term = $request->input('term', '');
            $families = $this->familyService->searchFamilies($term, $filters, 15);
        }
        
        return view('admin.reports.index', compact(
            'regions',
            'charities',
            'insuranceCompanies',
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
        
        $filters = $request->only(['charity_id', 'insurance_id', 'region_id', 'status', 'date_from', 'date_to']);
        
        return Excel::download(
            new FamiliesExport($filters),
            'families_report_' . date('Y-m-d') . '.xlsx'
        );
    }
} 
