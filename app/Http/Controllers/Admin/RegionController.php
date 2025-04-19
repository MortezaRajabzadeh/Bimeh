<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegionRequest;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RegionController extends Controller
{
    /**
     * نمایش لیست مناطق
     */
    public function index()
    {
        Gate::authorize('view regions');
        
        $regions = Region::withCount('families')->get();
        
        return view('admin.regions.index', compact('regions'));
    }

    /**
     * نمایش فرم ایجاد منطقه جدید
     */
    public function create()
    {
        Gate::authorize('create region');
        
        return view('admin.regions.create');
    }

    /**
     * ذخیره منطقه جدید
     */
    public function store(RegionRequest $request)
    {
        Gate::authorize('create region');
        
        $validated = $request->validated();
        
        Region::create($validated);
        
        return redirect()->route('admin.regions.index')
            ->with('success', 'منطقه با موفقیت ایجاد شد.');
    }

    /**
     * نمایش اطلاعات منطقه
     */
    public function show(Region $region)
    {
        Gate::authorize('view regions');
        
        $families = $region->families()->paginate(15);
        
        return view('admin.regions.show', compact('region', 'families'));
    }

    /**
     * نمایش فرم ویرایش منطقه
     */
    public function edit(Region $region)
    {
        Gate::authorize('edit region');
        
        return view('admin.regions.edit', compact('region'));
    }

    /**
     * به‌روزرسانی اطلاعات منطقه
     */
    public function update(RegionRequest $request, Region $region)
    {
        Gate::authorize('edit region');
        
        $validated = $request->validated();
        
        $region->update($validated);
        
        return redirect()->route('admin.regions.index')
            ->with('success', 'اطلاعات منطقه با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف منطقه
     */
    public function destroy(Region $region)
    {
        Gate::authorize('delete region');
        
        // بررسی وابستگی‌ها قبل از حذف
        if ($region->families()->count() > 0) {
            return back()->with('error', 'این منطقه دارای خانواده‌های وابسته است و نمی‌توان آن را حذف کرد.');
        }
        
        $region->delete();
        
        return redirect()->route('admin.regions.index')
            ->with('success', 'منطقه با موفقیت حذف شد.');
    }
} 