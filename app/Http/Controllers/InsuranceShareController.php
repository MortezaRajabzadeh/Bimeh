<?php

namespace App\Http\Controllers;

use App\Models\InsuranceShare;
use App\Models\FamilyInsurance;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Family;

class InsuranceShareController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = InsuranceShare::with(['familyInsurance.family', 'payerOrganization', 'payerUser']);

        // فیلتر بر اساس نوع پرداخت‌کننده
        if ($request->filled('payer_type')) {
            $query->where('payer_type', $request->payer_type);
        }

        // فیلتر بر اساس وضعیت پرداخت
        if ($request->filled('is_paid')) {
            $query->where('is_paid', $request->boolean('is_paid'));
        }

        // جستجو در نام پرداخت‌کننده
        if ($request->filled('search')) {
            $query->where('payer_name', 'like', '%' . $request->search . '%');
        }

        $shares = $query->paginate(15);

        return view('insurance.shares.index', compact('shares'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $familyInsurance = null;
        
        if ($request->filled('family_insurance_id')) {
            $familyInsurance = FamilyInsurance::with('family')->findOrFail($request->family_insurance_id);
        }

        // گرفتن لیست خانواده‌های بیمه شده
        $familyInsurances = FamilyInsurance::with('family')->get();
        $organizations = Organization::active()->get();
        $users = User::active()->get();

        $payerTypes = [
            'insurance_company' => 'شرکت بیمه',
            'charity' => 'خیریه',
            'bank' => 'بانک',
            'government' => 'دولت',
            'individual_donor' => 'فرد خیر',
            'csr_budget' => 'بودجه CSR',
            'other' => 'سایر',
        ];

        return view('insurance.shares.create', compact('familyInsurance', 'familyInsurances', 'organizations', 'users', 'payerTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'family_insurance_id' => 'required|exists:family_insurances,id',
            'percentage' => 'required|numeric|min:0.01|max:100',
            'payer_type' => 'required|in:insurance_company,charity,bank,government,individual_donor,csr_budget,other',
            'payer_name' => 'required|string|max:255',
            'payer_organization_id' => 'nullable|exists:organizations,id',
            'payer_user_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
        ]);

        // بررسی اینکه مجموع درصدها از ۱۰۰٪ تجاوز نکند
        $currentTotal = InsuranceShare::where('family_insurance_id', $validated['family_insurance_id'])
            ->sum('percentage');

        if ($currentTotal + $validated['percentage'] > 100) {
            return back()->withErrors([
                'percentage' => 'مجموع درصدهای سهم‌بندی نمی‌تواند از ۱۰۰٪ بیشتر باشد. درصد باقیمانده: ' . (100 - $currentTotal) . '٪'
            ])->withInput();
        }

        $share = InsuranceShare::create($validated);
        
        // محاسبه مبلغ بر اساس درصد
        $familyInsurance = FamilyInsurance::find($validated['family_insurance_id']);
        $share->calculateAmount($familyInsurance->premium_amount);
        $share->save();

        return redirect()->route('insurance.shares.index')
            ->with('success', 'سهم بیمه با موفقیت ایجاد شد.');
    }

    /**
     * Display the specified resource.
     */
    public function show(InsuranceShare $share)
    {
        $share->load(['familyInsurance.family', 'payerOrganization', 'payerUser']);
        
        return view('insurance.shares.show', compact('share'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(InsuranceShare $share)
    {
        $share->load('familyInsurance.family');
        
        $organizations = Organization::active()->get();
        $users = User::active()->get();

        $payerTypes = [
            'insurance_company' => 'شرکت بیمه',
            'charity' => 'خیریه',
            'bank' => 'بانک',
            'government' => 'دولت',
            'individual_donor' => 'فرد خیر',
            'csr_budget' => 'بودجه CSR',
            'other' => 'سایر',
        ];

        return view('insurance.shares.edit', compact('share', 'organizations', 'users', 'payerTypes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, InsuranceShare $share)
    {
        $validated = $request->validate([
            'percentage' => 'required|numeric|min:0.01|max:100',
            'payer_type' => 'required|in:insurance_company,charity,bank,government,individual_donor,csr_budget,other',
            'payer_name' => 'required|string|max:255',
            'payer_organization_id' => 'nullable|exists:organizations,id',
            'payer_user_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'is_paid' => 'boolean',
            'payment_date' => 'nullable|date',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        // بررسی اینکه مجموع درصدها از ۱۰۰٪ تجاوز نکند
        $currentTotal = InsuranceShare::where('family_insurance_id', $share->family_insurance_id)
            ->where('id', '!=', $share->id)
            ->sum('percentage');

        if ($currentTotal + $validated['percentage'] > 100) {
            return back()->withErrors([
                'percentage' => 'مجموع درصدهای سهم‌بندی نمی‌تواند از ۱۰۰٪ بیشتر باشد. درصد باقیمانده: ' . (100 - $currentTotal) . '٪'
            ])->withInput();
        }

        $share->update($validated);
        
        // محاسبه مجدد مبلغ
        $familyInsurance = $share->familyInsurance;
        $share->calculateAmount($familyInsurance->premium_amount);
        $share->save();

        return redirect()->route('insurance.shares.index')
            ->with('success', 'سهم بیمه با موفقیت به‌روزرسانی شد.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(InsuranceShare $share)
    {
        $share->delete();

        return redirect()->route('insurance.shares.index')
            ->with('success', 'سهم بیمه با موفقیت حذف شد.');
    }

    /**
     * نمایش سهم‌های یک بیمه خانواده خاص
     */
    public function byFamilyInsurance(FamilyInsurance $familyInsurance)
    {
        $shares = $familyInsurance->shares()->with(['payerOrganization', 'payerUser'])->get();
        $totalPercentage = $shares->sum('percentage');
        $remainingPercentage = 100 - $totalPercentage;

        return view('insurance.shares.by-family', compact('familyInsurance', 'shares', 'totalPercentage', 'remainingPercentage'));
    }

    /**
     * علامت‌گذاری سهم به عنوان پرداخت شده
     */
    public function markAsPaid(Request $request, InsuranceShare $share)
    {
        $validated = $request->validate([
            'payment_date' => 'required|date',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $share->update([
            'is_paid' => true,
            'payment_date' => $validated['payment_date'],
            'payment_reference' => $validated['payment_reference'],
        ]);

        return back()->with('success', 'سهم به عنوان پرداخت شده علامت‌گذاری شد.');
    }

    /**
     * صفحه مدیریت سهم‌بندی Real-Time
     */
    public function manage(Request $request)
    {
        // گرفتن لیست خانواده‌هایی که بیمه دارند
        $families = Family::whereHas('insurance')->with('insurance')->get();
        
        $selectedFamily = null;
        $familyInsurance = null;
        
        if ($request->filled('family_id')) {
            $selectedFamily = Family::with(['insurance', 'members'])->find($request->family_id);
            if ($selectedFamily && $selectedFamily->insurance) {
                $familyInsurance = $selectedFamily->insurance;
            }
        }
        
        return view('insurance.shares.manage', compact('families', 'selectedFamily', 'familyInsurance'));
    }
}
