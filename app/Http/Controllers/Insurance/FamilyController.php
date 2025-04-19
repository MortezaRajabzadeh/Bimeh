<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyStatusRequest;
use App\Models\Family;
use App\Models\Organization;
use App\Services\FamilyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FamilyController extends Controller
{
    protected FamilyService $familyService;

    public function __construct(FamilyService $familyService)
    {
        $this->familyService = $familyService;
    }

    /**
     * نمایش لیست خانواده‌ها
     */
    public function index(Request $request)
    {
        Gate::authorize('view families');
        
        $status = $request->input('status', 'pending');
        $families = $this->familyService->getFamiliesByStatus($status);
        
        return view('insurance.families.index', compact('families', 'status'));
    }

    /**
     * نمایش جزئیات خانواده
     */
    public function show(Family $family)
    {
        Gate::authorize('view families');
        
        $members = $family->members;
        $head = $family->head();
        $insuranceCompanies = Organization::insurance()->active()->get();
        
        return view('insurance.families.show', compact('family', 'members', 'head', 'insuranceCompanies'));
    }

    /**
     * به‌روزرسانی وضعیت خانواده
     */
    public function updateStatus(FamilyStatusRequest $request, Family $family)
    {
        Gate::authorize('change family status');
        
        $validated = $request->validated();
        
        $status = $validated['status'];
        $reason = $validated['rejection_reason'] ?? null;
        $insuranceId = $validated['insurance_id'] ?? null;
        
        $insurance = $insuranceId ? Organization::findOrFail($insuranceId) : null;
        
        $this->familyService->changeStatus($family, $status, $reason, $insurance);
        
        return redirect()->route('insurance.families.index', ['status' => $status])
            ->with('success', "وضعیت خانواده با موفقیت به «{$this->getStatusLabel($status)}» تغییر یافت.");
    }
    
    /**
     * دریافت برچسب فارسی وضعیت
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'pending' => 'در انتظار بررسی',
            'reviewing' => 'در حال بررسی',
            'approved' => 'تأیید شده',
            'rejected' => 'رد شده',
        ];
        
        return $labels[$status] ?? $status;
    }
} 