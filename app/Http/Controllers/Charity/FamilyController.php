<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyRequest;
use App\Models\Family;
use App\Models\Region;
use App\Services\FamilyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use App\Models\Member;
use Illuminate\Support\Facades\Auth;

class FamilyController extends Controller
{
    protected FamilyService $familyService;

    public function __construct(FamilyService $familyService)
    {
        $this->familyService = $familyService;
    }

    /**
     * نمایش لیست همه خانواده‌ها
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $families = Family::where('charity_id', request()->user()->organization_id)
                        ->paginate(15);
                        
        return view('charity.families.index', compact('families'));
    }
    
    /**
     * نمایش لیست خانواده‌های بیمه شده
     *
     * @return \Illuminate\View\View
     */
    public function insuredFamilies()
    {
        $families = Family::where('charity_id', request()->user()->organization_id)
                        ->where('is_insured', true)
                        ->paginate(15);
        
        return view('charity.families.insured', compact('families'));
    }
    
    /**
     * نمایش لیست خانواده‌های بدون بیمه
     *
     * @return \Illuminate\View\View
     */
    public function uninsuredFamilies()
    {
        $families = Family::where('charity_id', request()->user()->organization_id)
                        ->where('is_insured', false)
                        ->paginate(15);
        
        return view('charity.families.uninsured', compact('families'));
    }

    /**
     * نمایش فرم ایجاد خانواده جدید
     */
    public function create()
    {
        Gate::authorize('create family');
        
        $regions = Region::active()->get();
        
        return view('charity.families.create', compact('regions'));
    }

    /**
     * ذخیره خانواده جدید
     */
    public function store(FamilyRequest $request)
    {
        Gate::authorize('create family');
        
        $validated = $request->validated();
        
        $family = $this->familyService->registerFamily($validated, $request->user());
        
        return redirect()->route('dashboard')
            ->with('success', 'خانواده با موفقیت ثبت شد. لطفاً اعضای خانواده را اضافه کنید.');
    }

    /**
     * نمایش اطلاعات خانواده
     */
    public function show(Family $family)
    {
        Gate::authorize('view own families');
        
        // اطمینان از اینکه خانواده متعلق به سازمان کاربر جاری است
        if ($family->charity_id !== request()->user()->organization_id) {
            abort(403, 'شما به این خانواده دسترسی ندارید.');
        }
        
        $members = $family->members;
        
        return view('charity.families.show', [
            'family' => $family,
            'members' => $members,
        ]);
    }

    /**
     * نمایش فرم ویرایش خانواده
     */
    public function edit(Family $family)
    {
        Gate::authorize('edit family');
        
        // اطمینان از اینکه خانواده متعلق به سازمان کاربر جاری است
        if ($family->charity_id !== request()->user()->organization_id) {
            abort(403, 'شما به این خانواده دسترسی ندارید.');
        }
        
        $regions = Region::active()->get();
        
        return view('charity.families.edit', compact('family', 'regions'));
    }

    /**
     * به‌روزرسانی اطلاعات خانواده
     */
    public function update(FamilyRequest $request, Family $family)
    {
        Gate::authorize('edit family');
        
        // اطمینان از اینکه خانواده متعلق به سازمان کاربر جاری است
        if ($family->charity_id !== request()->user()->organization_id) {
            abort(403, 'شما به این خانواده دسترسی ندارید.');
        }
        
        $validated = $request->validated();
        
        // احتیاط: برخی فیلدها نباید توسط خیریه‌ها تغییر کنند
        unset($validated['status']);
        unset($validated['insurance_id']);
        unset($validated['verified_at']);
        
        // پردازش معیارهای پذیرش
        if (isset($validated['acceptance_criteria_array'])) {
            // تبدیل معیارهای وارد شده به آرایه
            // مثال: اگر فرم به صورت رشته با جداکننده کاما باشد
            $criteria = explode(',', $validated['acceptance_criteria_array']);
            $criteria = array_map('trim', $criteria); // حذف فاصله‌های اضافی
            $criteria = array_filter($criteria); // حذف مقادیر خالی
            
            // ذخیره به صورت آرایه
            $validated['acceptance_criteria'] = $criteria;
            
            // حذف فیلد اضافی از داده‌های اصلی
            unset($validated['acceptance_criteria_array']);
        }
        
        $family->update($validated);
        
        return redirect()->route('dashboard')
            ->with('success', 'اطلاعات خانواده با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف خانواده
     */
    public function destroy(Family $family)
    {
        Gate::authorize('delete family');
        
        // اطمینان از اینکه خانواده متعلق به سازمان کاربر جاری است
        if ($family->charity_id !== request()->user()->organization_id) {
            abort(403, 'شما به این خانواده دسترسی ندارید.');
        }
        
        // فقط خانواده‌هایی که هنوز تایید نشده‌اند قابل حذف هستند
        if ($family->status !== 'pending') {
            return back()->with('error', 'فقط خانواده‌های در انتظار تایید قابل حذف هستند.');
        }
        
        $family->delete();
        
        return redirect()->route('charity.families.index')
            ->with('success', 'خانواده با موفقیت حذف شد.');
    }

    /**
     * دانلود فایل اکسل خانواده‌ها
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        try {
            $charity_id = Auth::user()->organization_id;
            $query = $request->input('q');
            $status = $request->input('status');
            $province_id = $request->input('province_id');
            $city_id = $request->input('city_id');
            $district_id = $request->input('district_id');
            $region_id = $request->input('region_id');
            $organization_id = $request->input('organization_id');
            $wizard_status = $request->input('wizard_status');
            $deprivation_rank = $request->input('deprivation_rank');
            $family_rank_range = $request->input('family_rank_range');
            $specific_criteria = $request->input('specific_criteria');
            
            $filters = [];
            
            // اضافه کردن فیلترهای اضافی اگر وجود داشته باشند
            if ($charity_id) {
                $filters['charity_id'] = $charity_id;
            }
            
            if ($status === 'insured') {
                $filters['is_insured'] = true;
            } elseif ($status === 'uninsured') {
                $filters['is_insured'] = false;
            }
            
            if ($province_id) {
                $filters['province_id'] = $province_id;
            }
            
            if ($city_id) {
                $filters['city_id'] = $city_id;
            }
            
            if ($district_id) {
                $filters['district_id'] = $district_id;
            }
            
            if ($region_id) {
                $filters['region_id'] = $region_id;
            }
            
            if ($organization_id) {
                $filters['organization_id'] = $organization_id;
            }
            
            if ($wizard_status) {
                $filters['wizard_status'] = $wizard_status;
            }
            
            if ($deprivation_rank) {
                $filters['deprivation_rank'] = $deprivation_rank;
            }
            
            if ($family_rank_range) {
                $filters['family_rank_range'] = $family_rank_range;
            }
            
            if ($specific_criteria) {
                $filters['specific_criteria'] = $specific_criteria;
            }
            
            if ($query) {
                $filters['search'] = $query;
            }
            
            $fileName = 'families_' . now()->format('Y_m_d_H_i_s') . '.xlsx';
            
            return \Maatwebsite\Excel\Facades\Excel::download(
                new \App\Exports\FamiliesExport($filters),
                $fileName
            );
        } catch (\Exception $e) {
            return back()->with('error', 'خطا در دانلود فایل اکسل: ' . $e->getMessage());
        }
    }

    /**
     * نمایش داشبورد خیریه
     */
    public function dashboard()
    {
        $charity_id = Auth::user()->organization_id;
        
        $insuredFamilies = Family::where('charity_id', $charity_id)
            ->where('is_insured', true)
            ->count();
            
        $insuredMembers = Member::whereHas('family', function($query) use ($charity_id) {
            $query->where('charity_id', $charity_id)
                ->where('is_insured', true);
        })->count();
        
        $uninsuredFamilies = Family::where('charity_id', $charity_id)
            ->where('is_insured', false)
            ->count();
            
        $uninsuredMembers = Member::whereHas('family', function($query) use ($charity_id) {
            $query->where('charity_id', $charity_id)
                ->where('is_insured', false);
        })->count();
        
        return view('charity.dashboard', compact(
            'insuredFamilies', 
            'insuredMembers', 
            'uninsuredFamilies', 
            'uninsuredMembers'
        ));
    }

    /**
     * نمایش فرم افزودن خانواده جدید
     */
    public function addFamily()
    {
        return view('charity.add-family');
    }

    /**
     * جستجوی خانواده‌ها
     */
    public function search(Request $request)
    {
        $charity_id = Auth::user()->organization_id;
        $query = $request->input('q');
        $status = $request->input('status');
        
        $families = Family::where('charity_id', $charity_id);
        
        // جستجو بر اساس وضعیت بیمه
        if ($status === 'insured') {
            $families->where('is_insured', true);
        } elseif ($status === 'uninsured') {
            $families->where('is_insured', false);
        }
        
        // جستجو بر اساس کلیدواژه
        if (!empty($query)) {
            $families->where(function($q) use ($query) {
                // جستجو در کد خانواده
                $q->where('family_code', 'like', "%{$query}%");
                
                // جستجو در نام/کد ملی سرپرست
                $q->orWhereHas('members', function($member) use ($query) {
                    $member->where('is_head', true)
                        ->where(function($m) use ($query) {
                            $m->where('first_name', 'like', "%{$query}%")
                              ->orWhere('last_name', 'like', "%{$query}%")
                              ->orWhere('national_code', 'like', "%{$query}%");
                        });
                });
            });
        }
        
        $results = $families->paginate(10);
        
        return view('charity.search-results', compact('results', 'query', 'status'));
    }
}
