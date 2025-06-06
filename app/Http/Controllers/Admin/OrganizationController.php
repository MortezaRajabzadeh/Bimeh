<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationRequest;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

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
        $query = Organization::query();
        
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }
        
        $organizations = $query->paginate(10);
        
        return view('admin.organizations.index', compact('organizations'));
    }

    /**
     * نمایش فرم ایجاد سازمان جدید
     */
    public function create()
    {
        return view('admin.organizations.create');
    }

    /**
     * ذخیره سازمان جدید
     */
    public function store(Request $request)
    {
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|max:1024',
        ]);

        $organization = new Organization();
        $organization->name = $validated['name'];
        $organization->type = $validated['type'];
        $organization->description = $validated['description'] ?? null;
        $organization->address = $validated['address'] ?? null;
        
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('organizations', 'public');
            $organization->logo_path = $path;
        }
        
        $organization->save();
        
        return redirect()->route('admin.organizations.index')
            ->with('success', 'سازمان با موفقیت ایجاد شد');
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
        return view('admin.organizations.edit', compact('organization'));
    }

    /**
     * به‌روزرسانی اطلاعات سازمان
     */
    public function update(Request $request, Organization $organization)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|max:1024',
        ]);

        $organization->name = $validated['name'];
        $organization->type = $validated['type'];
        $organization->description = $validated['description'] ?? null;
        $organization->address = $validated['address'] ?? null;
        
        if ($request->hasFile('logo')) {
            // Remove old logo if exists
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }
            
            $path = $request->file('logo')->store('organizations', 'public');
            $organization->logo_path = $path;
        }
        
        $organization->save();
        
        return redirect()->route('admin.organizations.index')
            ->with('success', 'سازمان با موفقیت بروزرسانی شد');
    }

    /**
     * حذف سازمان
     */
    public function destroy(Organization $organization)
    {
        // Check if organization has users
        if ($organization->users()->count() > 0) {
            return redirect()->route('admin.organizations.index')
                ->with('error', 'این سازمان دارای کاربر است و نمی‌توان آن را حذف کرد');
        }
        
        // Delete logo if exists
        if ($organization->logo_path) {
            Storage::disk('public')->delete($organization->logo_path);
        }
        
        $organization->delete();
        
        return redirect()->route('admin.organizations.index')
            ->with('success', 'سازمان با موفقیت حذف شد');
    }
    
    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('selected_ids', []);
        
        if (empty($ids)) {
            return redirect()->route('admin.organizations.index')
                ->with('error', 'هیچ سازمانی انتخاب نشده است');
        }
        
        $organizations = Organization::whereIn('id', $ids)->get();
        
        foreach ($organizations as $organization) {
            // Skip organizations with users
            if ($organization->users()->count() > 0) {
                continue;
            }
            
            // Delete logo if exists
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }
            
            $organization->delete();
        }
        
        return redirect()->route('admin.organizations.index')
            ->with('success', 'سازمان‌های انتخاب شده با موفقیت حذف شدند');
    }
}