<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FamilyFundingAllocation;
use App\Models\FundingSource;
use App\Services\FamilyFundingAllocationService;
use App\Http\Requests\FamilyFundingAllocationRequest;

class FamilyFundingAllocationController extends Controller
{
    protected $allocationService;

    public function __construct(FamilyFundingAllocationService $allocationService)
    {
        $this->allocationService = $allocationService;
    }

    /**
     * نمایش لیست تخصیص‌های بودجه
     */
    public function index(Request $request)
    {
        $filters = $request->only(['family_id', 'funding_source_id', 'status']);
        
        // جستجوی کد خانواده
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $families = \App\Models\Family::where('family_code', 'like', "%{$searchTerm}%")
                ->orWhereHas('members', function($query) use ($searchTerm) {
                    $query->where('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%")
                        ->orWhere('national_code', 'like', "%{$searchTerm}%");
                })
                ->get();
            
            $filters['family_ids'] = $families->pluck('id')->toArray();
        }
        
        $allocations = $this->allocationService->getAllocations($filters);
        $fundingSources = FundingSource::where('is_active', true)->orderBy('name')->get();
        
        return view('insurance.allocations.index', [
            'allocations' => $allocations,
            'fundingSources' => $fundingSources,
            'filters' => $filters
        ]);
    }

    /**
     * نمایش فرم ایجاد تخصیص جدید
     */
    public function create()
    {
        $fundingSources = FundingSource::where('is_active', true)->orderBy('name')->get();
        return view('insurance.allocations.create', compact('fundingSources'));
    }

    /**
     * ذخیره تخصیص جدید
     */
    public function store(FamilyFundingAllocationRequest $request)
    {
        try {
            $result = $this->allocationService->createAllocation($request->validated());
            return redirect()->route('insurance.allocations.index')
                ->with('success', 'تخصیص بودجه با موفقیت ایجاد شد.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * نمایش جزئیات یک تخصیص
     */
    public function show($id)
    {
        $allocation = FamilyFundingAllocation::with(['family.members', 'fundingSource', 'creator', 'approver'])
            ->findOrFail($id);
            
        return view('insurance.allocations.show', compact('allocation'));
    }
    
    /**
     * نمایش فرم ویرایش تخصیص
     */
    public function edit($id)
    {
        $allocation = FamilyFundingAllocation::findOrFail($id);
        $fundingSources = FundingSource::where('is_active', true)->orderBy('name')->get();
        
        return view('insurance.allocations.edit', compact('allocation', 'fundingSources'));
    }
    
    /**
     * به‌روزرسانی تخصیص
     */
    public function update(FamilyFundingAllocationRequest $request, $id)
    {
        try {
            $allocation = FamilyFundingAllocation::findOrFail($id);
            $this->allocationService->updateAllocation($allocation, $request->validated());
            
            return redirect()->route('insurance.allocations.show', $allocation->id)
                ->with('success', 'تخصیص بودجه با موفقیت به‌روزرسانی شد.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }
    
    /**
     * تایید تخصیص
     */
    public function approve($id)
    {
        try {
            $allocation = FamilyFundingAllocation::findOrFail($id);
            $this->allocationService->approveAllocation($allocation);
            
            return redirect()->back()->with('success', 'تخصیص با موفقیت تایید شد.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * علامت‌گذاری به عنوان پرداخت شده
     */
    public function markAsPaid($id)
    {
        try {
            $allocation = FamilyFundingAllocation::findOrFail($id);
            $this->allocationService->markAllocationAsPaid($allocation);
            
            return redirect()->back()->with('success', 'تخصیص با موفقیت به عنوان پرداخت شده علامت‌گذاری شد.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * حذف تخصیص
     */
    public function destroy($id)
    {
        try {
            $allocation = FamilyFundingAllocation::findOrFail($id);
            
            // فقط تخصیص‌های در انتظار قابل حذف هستند
            if ($allocation->status !== FamilyFundingAllocation::STATUS_PENDING) {
                return redirect()->back()->withErrors(['error' => 'فقط تخصیص‌های در انتظار قابل حذف هستند.']);
            }
            
            $allocation->delete();
            
            return redirect()->route('insurance.allocations.index')
                ->with('success', 'تخصیص بودجه با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
