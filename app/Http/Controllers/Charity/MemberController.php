<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Http\Requests\MemberRequest;
use App\Models\Family;
use App\Models\Member;
use App\Services\FamilyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MemberController extends Controller
{
    protected FamilyService $familyService;

    public function __construct(FamilyService $familyService)
    {
        $this->familyService = $familyService;
    }

    /**
     * نمایش لیست اعضای خانواده
     */
    public function index(Family $family)
    {
        Gate::authorize('view members');
        
        // اطمینان از اینکه خانواده متعلق به سازمان کاربر جاری است
        if ($family->charity_id !== request()->user()->organization_id) {
            abort(403, 'شما به این خانواده دسترسی ندارید.');
        }
        
        $members = $family->members;
        $head = $family->head();
        
        return view('charity.members.index', compact('family', 'members', 'head'));
    }

    /**
     * نمایش فرم ایجاد عضو جدید
     */
    public function create(Family $family)
    {
        Gate::authorize('create member');
        
        // اطمینان از اینکه خانواده متعلق به سازمان کاربر جاری است
        if ($family->charity_id !== request()->user()->organization_id) {
            abort(403, 'شما به این خانواده دسترسی ندارید.');
        }
        
        return view('charity.members.create', compact('family'));
    }

    /**
     * ذخیره عضو جدید
     */
    public function store(MemberRequest $request, Family $family)
    {
        Gate::authorize('create member');
        
        // اطمینان از اینکه خانواده متعلق به سازمان کاربر جاری است
        if ($family->charity_id !== $request->user()->organization_id) {
            abort(403, 'شما به این خانواده دسترسی ندارید.');
        }
        
        $validated = $request->validated();
        
        $member = $this->familyService->addMember($family, $validated);
        
        return redirect()->route('charity.families.members.index', $family)
            ->with('success', 'عضو جدید با موفقیت به خانواده اضافه شد.');
    }

    /**
     * نمایش فرم ویرایش عضو
     */
    public function edit(Family $family, Member $member)
    {
        Gate::authorize('edit member');
        
        // اطمینان از اینکه خانواده متعلق به سازمان کاربر جاری است
        if ($family->charity_id !== request()->user()->organization_id) {
            abort(403, 'شما به این خانواده دسترسی ندارید.');
        }
        
        // اطمینان از اینکه عضو متعلق به همین خانواده است
        if ($member->family_id !== $family->id) {
            abort(404, 'عضو مورد نظر یافت نشد.');
        }
        
        return view('charity.members.edit', compact('family', 'member'));
    }

    /**
     * به‌روزرسانی اطلاعات عضو
     */
    public function update(MemberRequest $request, Family $family, Member $member)
    {
        Gate::authorize('edit member');
        
        // اطمینان از اینکه خانواده متعلق به سازمان کاربر جاری است
        if ($family->charity_id !== $request->user()->organization_id) {
            abort(403, 'شما به این خانواده دسترسی ندارید.');
        }
        
        // اطمینان از اینکه عضو متعلق به همین خانواده است
        if ($member->family_id !== $family->id) {
            abort(404, 'عضو مورد نظر یافت نشد.');
        }
        
        $validated = $request->validated();
        
        // اگر این عضو به عنوان سرپرست جدید انتخاب شده است
        if (!$member->is_head && isset($validated['is_head']) && $validated['is_head']) {
            // سرپرست قبلی را پیدا کرده و وضعیت سرپرستی را حذف می‌کنیم
            $oldHead = $family->members()->where('is_head', true)->first();
            if ($oldHead) {
                $oldHead->update(['is_head' => false]);
            }
        }
        
        $member->update($validated);
        
        return redirect()->route('charity.families.members.index', $family)
            ->with('success', 'اطلاعات عضو با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف عضو
     */
    public function destroy(Family $family, Member $member)
    {
        Gate::authorize('delete member');
        
        // اطمینان از اینکه خانواده متعلق به سازمان کاربر جاری است
        if ($family->charity_id !== request()->user()->organization_id) {
            abort(403, 'شما به این خانواده دسترسی ندارید.');
        }
        
        // اطمینان از اینکه عضو متعلق به همین خانواده است
        if ($member->family_id !== $family->id) {
            abort(404, 'عضو مورد نظر یافت نشد.');
        }
        
        // اگر این عضو سرپرست خانوار است
        if ($member->is_head) {
            return back()->with('error', 'سرپرست خانوار را نمی‌توان حذف کرد. ابتدا یک عضو دیگر را به عنوان سرپرست تعیین کنید.');
        }
        
        $this->familyService->removeMember($member);
        
        return redirect()->route('charity.families.members.index', $family)
            ->with('success', 'عضو با موفقیت حذف شد.');
    }
} 