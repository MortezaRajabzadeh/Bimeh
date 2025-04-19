<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationRequest;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrganizationController extends Controller
{
    protected OrganizationService $organizationService;

    public function __construct(OrganizationService $organizationService)
    {
        $this->organizationService = $organizationService;
    }

    /**
     * نمایش لیست سازمان‌ها
     */
    public function index(Request $request)
    {
        Gate::authorize('view organizations');
        
        $type = $request->input('type');
        
        if ($type === 'insurance') {
            $organizations = $this->organizationService->getInsuranceCompanies();
        } elseif ($type === 'charity') {
            $organizations = $this->organizationService->getCharities();
        } else {
            $organizations = $this->organizationService->getAllOrganizations();
        }
        
        return view('admin.organizations.index', compact('organizations'));
    }

    /**
     * نمایش فرم ایجاد سازمان جدید
     */
    public function create()
    {
        Gate::authorize('create organization');
        
        return view('admin.organizations.create');
    }

    /**
     * ذخیره سازمان جدید
     */
    public function store(OrganizationRequest $request)
    {
        Gate::authorize('create organization');
        
        $validated = $request->validated();
        $logo = $request->hasFile('logo') ? $request->file('logo') : null;
        
        $organization = $this->organizationService->createOrganization($validated, $logo);
        
        return redirect()->route('admin.organizations.index')
            ->with('success', 'سازمان با موفقیت ایجاد شد.');
    }

    /**
     * نمایش اطلاعات سازمان
     */
    public function show(Organization $organization)
    {
        Gate::authorize('view organizations');
        
        return view('admin.organizations.show', compact('organization'));
    }

    /**
     * نمایش فرم ویرایش سازمان
     */
    public function edit(Organization $organization)
    {
        Gate::authorize('edit organization');
        
        return view('admin.organizations.edit', compact('organization'));
    }

    /**
     * به‌روزرسانی اطلاعات سازمان
     */
    public function update(OrganizationRequest $request, Organization $organization)
    {
        Gate::authorize('edit organization');
        
        $validated = $request->validated();
        $logo = $request->hasFile('logo') ? $request->file('logo') : null;
        
        $this->organizationService->updateOrganization($organization, $validated, $logo);
        
        return redirect()->route('admin.organizations.index')
            ->with('success', 'اطلاعات سازمان با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف سازمان
     */
    public function destroy(Organization $organization)
    {
        Gate::authorize('delete organization');
        
        // بررسی وابستگی‌ها قبل از حذف
        if ($organization->users()->count() > 0) {
            return back()->with('error', 'این سازمان دارای کاربران وابسته است و نمی‌توان آن را حذف کرد.');
        }
        
        if ($organization->registeredFamilies()->count() > 0 || $organization->insuredFamilies()->count() > 0) {
            return back()->with('error', 'این سازمان دارای خانواده‌های وابسته است و نمی‌توان آن را حذف کرد.');
        }
        
        $organization->delete();
        
        return redirect()->route('admin.organizations.index')
            ->with('success', 'سازمان با موفقیت حذف شد.');
    }
} 