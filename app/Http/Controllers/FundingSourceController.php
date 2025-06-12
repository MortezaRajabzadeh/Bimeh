<?php

namespace App\Http\Controllers;

use App\Models\FundingSource;
use Illuminate\Http\Request;
use App\Http\Requests\FundingSourceRequest;

class FundingSourceController extends Controller
{
    /**
     * نمایش لیست منابع تأمین مالی
     */
    public function index()
    {
        $sources = FundingSource::orderBy('name')->paginate(20);
        return view('insurance.funding-sources.index', compact('sources'));
    }

    /**
     * نمایش فرم ایجاد منبع جدید
     */
    public function create()
    {
        return view('insurance.funding-sources.create');
    }

    /**
     * ذخیره منبع مالی جدید
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'source_type' => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
            'annual_budget' => 'nullable|numeric|min:0',
            'contact_info' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);
        
        $validated['is_active'] = $request->has('is_active') ? true : false;
        
        FundingSource::create($validated);
        
        return redirect()->route('insurance.funding-sources.index')
            ->with('success', 'منبع تأمین مالی با موفقیت ایجاد شد.');
    }

    /**
     * نمایش جزئیات منبع مالی
     */
    public function show(FundingSource $fundingSource)
    {
        $allocations = $fundingSource->allocations()->with('family')->paginate(10);
        $transactions = $fundingSource->transactions()->paginate(10);
        
        return view('insurance.funding-sources.show', compact('fundingSource', 'allocations', 'transactions'));
    }

    /**
     * نمایش فرم ویرایش منبع مالی
     */
    public function edit(FundingSource $fundingSource)
    {
        return view('insurance.funding-sources.edit', compact('fundingSource'));
    }

    /**
     * به‌روزرسانی منبع مالی
     */
    public function update(Request $request, FundingSource $fundingSource)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|max:50',
            'source_type' => 'required|string|max:50',
            'description' => 'nullable|string|max:500',
            'annual_budget' => 'nullable|numeric|min:0',
            'contact_info' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);
        
        $validated['is_active'] = $request->has('is_active') ? true : false;
        
        $fundingSource->update($validated);
        
        return redirect()->route('insurance.funding-sources.index')
            ->with('success', 'منبع تأمین مالی با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف منبع مالی
     */
    public function destroy(FundingSource $fundingSource)
    {
        // بررسی اینکه منبع مالی در حال استفاده نباشد
        $allocationsCount = $fundingSource->allocations()->count();
        if ($allocationsCount > 0) {
            return back()->with('error', 'این منبع مالی در حال استفاده است و نمی‌تواند حذف شود.');
        }
        
        // حذف تراکنش‌های مرتبط
        $fundingSource->transactions()->delete();
        
        // حذف منبع مالی
        $fundingSource->delete();
        
        return redirect()->route('insurance.funding-sources.index')
            ->with('success', 'منبع تأمین مالی با موفقیت حذف شد.');
    }
} 
