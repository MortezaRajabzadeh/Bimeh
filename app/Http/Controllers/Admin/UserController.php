<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    /**
     * نمایش لیست کاربران
     */
    public function index(Request $request)
    {
        // Gate::authorize('view users');
        
        $userType = $request->input('user_type');
        
        $query = User::with(['organization', 'roles']);
        
        if ($userType) {
            $query->where('user_type', $userType);
        }
        
        $users = $query->paginate(15);
        
        // دریافت سازمان‌ها و نقش‌ها برای فرم ایجاد کاربر جدید
        $organizations = Organization::active()->get();
        $roles = Role::all();
        
        return view('admin.users.index', compact('users', 'userType', 'organizations', 'roles'));
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
    public function store(Request $request)
    {
        Gate::authorize('create user');

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255', 
            'national_code' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
        ], [
            // پیام‌های خطای فارسی
            'first_name.required' => 'وارد کردن نام الزامی است.',
            'last_name.required' => 'وارد کردن نام خانوادگی الزامی است.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'username.required' => 'وارد کردن نام کاربری الزامی است.',
            'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
            'password.required' => 'وارد کردن رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'رمز عبور و تکرار آن مطابقت ندارند.',
            'role.required' => 'انتخاب نقش الزامی است.',
            'role.exists' => 'نقش انتخاب شده معتبر نیست.',
        ]);

        try {
            DB::beginTransaction();

            // ساخت کاربر
            $user = User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'password' => Hash::make($validated['password']),
                'is_active' => true,
                'user_type' => $validated['role'],
            ]);
            
            // تخصیص کد ملی به صورت جداگانه در صورتی که در پایگاه داده وجود داشته باشد
            if (Schema::hasColumn('users', 'national_code')) {
                $user->national_code = $validated['national_code'] ?? null;
                $user->save();
            }
            


            // تخصیص نقش به کاربر
            $user->assignRole($validated['role']);

            DB::commit();
            return redirect()->route('admin.users.index')
                ->with('success', 'کاربر با موفقیت ایجاد شد');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'خطا در ایجاد کاربر: ' . $e->getMessage());
        }
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
        
        try {
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', 'کاربر با موفقیت حذف شد.');
        } catch (\Exception $e) {
            return redirect()->route('admin.users.index')->with('error', 'خطا در حذف کاربر: ' . $e->getMessage());
        }
    }
    
    /**
     * حذف دسته‌جمعی کاربران انتخاب شده
     */
    public function bulkDestroy(Request $request)
    {
        Gate::authorize('delete user');
        
        try {
            $selectedIds = $request->input('selected_ids', []);
            
            // اطمینان از عدم حذف خود کاربر
            if (in_array($request->user()->id, $selectedIds)) {
                return redirect()->route('admin.users.index')->with('error', 'شما نمی‌توانید حساب کاربری خود را حذف کنید.');
            }
            
            // حذف کاربران بدون وابستگی به خانواده‌ها
            $usersWithFamilies = User::whereIn('id', $selectedIds)
                ->withCount('registeredFamilies')
                ->having('registered_families_count', '>', 0)
                ->pluck('id')
                ->toArray();
            
            if (!empty($usersWithFamilies)) {
                return redirect()->route('admin.users.index')->with('error', 'برخی از کاربران دارای خانواده‌های ثبت شده هستند و نمی‌توان آنها را حذف کرد.');
            }
            
            $count = User::whereIn('id', $selectedIds)->count();
            User::whereIn('id', $selectedIds)->delete();
            
            return redirect()->route('admin.users.index')->with('success', $count . ' کاربر با موفقیت حذف شدند.');
        } catch (\Exception $e) {
            return redirect()->route('admin.users.index')->with('error', 'خطا در حذف کاربران: ' . $e->getMessage());
        }
    }

}
