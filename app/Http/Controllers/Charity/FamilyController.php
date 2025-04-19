<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Http\Requests\FamilyRequest;
use App\Models\Family;
use App\Models\Region;
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
        
        return redirect()->route('charity.families.show', $family)
            ->with('success', 'خانواده با موفقیت ثبت شد. لطفاً اعضای خانواده را اضافه کنید.');
    }

    /**
     * نمایش اطلاعات خانواده
     */
    public function show(Family $family)
    {
        Gate::authorize('view families');
        
        // اطمینان از اینکه خانواده متعلق به سازمان کاربر جاری است
        if ($family->charity_id !== request()->user()->organization_id) {
            abort(403, 'شما به این خانواده دسترسی ندارید.');
        }
        
        $members = $family->members;
        $head = $family->head();
        
        return view('charity.families.show', compact('family', 'members', 'head'));
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
        
        $family->update($validated);
        
        return redirect()->route('charity.families.show', $family)
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
    public function exportExcel()
    {
        // فعلاً برای جلوگیری از خطا، به صفحه قبل برمی‌گردیم
        // در آینده می‌توان از پکیج مناسب مثل maatwebsite/excel استفاده کرد
        return back()->with('success', 'فعلاً دانلود اکسل در حال پیاده‌سازی است.');
        
        // نمونه کد برای استفاده در آینده
        /*
        return Excel::download(new FamiliesExport(request()->user()->organization_id), 'families.xlsx');
        */
    }
} 