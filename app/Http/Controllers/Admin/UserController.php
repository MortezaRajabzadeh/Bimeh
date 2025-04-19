<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * نمایش لیست کاربران
     */
    public function index(Request $request)
    {
        Gate::authorize('view users');
        
        $userType = $request->input('user_type');
        
        $query = User::with('organization');
        
        if ($userType) {
            $query->where('user_type', $userType);
        }
        
        $users = $query->paginate(15);
        
        return view('admin.users.index', compact('users', 'userType'));
    }

    /**
     * نمایش فرم ایجاد کاربر جدید
     */
    public function create()
    {
        Gate::authorize('create user');
        
        $organizations = Organization::active()->get();
        $roles = Role::all();
        
        return view('admin.users.create', compact('organizations', 'roles'));
    }

    /**
     * ذخیره کاربر جدید
     */
    public function store(UserRequest $request)
    {
        Gate::authorize('create user');
        
        $validated = $request->validated();
        $validated['password'] = Hash::make($validated['password']);
        
        $user = User::create($validated);
        
        // تخصیص نقش به کاربر
        if ($request->input('role')) {
            $user->assignRole($request->input('role'));
        } else {
            // تخصیص نقش پیش‌فرض براساس نوع کاربر
            $user->assignRole($validated['user_type']);
        }
        
        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت ایجاد شد.');
    }

    /**
     * نمایش اطلاعات کاربر
     */
    public function show(User $user)
    {
        Gate::authorize('view users');
        
        return view('admin.users.show', compact('user'));
    }

    /**
     * نمایش فرم ویرایش کاربر
     */
    public function edit(User $user)
    {
        Gate::authorize('edit user');
        
        $organizations = Organization::active()->get();
        $roles = Role::all();
        
        return view('admin.users.edit', compact('user', 'organizations', 'roles'));
    }

    /**
     * به‌روزرسانی اطلاعات کاربر
     */
    public function update(UserRequest $request, User $user)
    {
        Gate::authorize('edit user');
        
        $validated = $request->validated();
        
        // اگر رمز عبور جدید وارد شده باشد، آن را هش می‌کنیم
        if (isset($validated['password']) && !empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }
        
        $user->update($validated);
        
        // به‌روزرسانی نقش کاربر
        if ($request->input('role')) {
            $user->syncRoles([$request->input('role')]);
        }
        
        return redirect()->route('admin.users.index')
            ->with('success', 'اطلاعات کاربر با موفقیت به‌روزرسانی شد.');
    }

    /**
     * حذف کاربر
     */
    public function destroy(Request $request, User $user)
    {
        Gate::authorize('delete user');
        
        // اطمینان از عدم حذف خود کاربر
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'شما نمی‌توانید حساب کاربری خود را حذف کنید.');
        }
        
        // بررسی وابستگی‌ها قبل از حذف
        if ($user->registeredFamilies()->count() > 0) {
            return back()->with('error', 'این کاربر دارای خانواده‌های ثبت شده است و نمی‌توان آن را حذف کرد.');
        }
        
        $user->delete();
        
        return redirect()->route('admin.users.index')
            ->with('success', 'کاربر با موفقیت حذف شد.');
    }
} 