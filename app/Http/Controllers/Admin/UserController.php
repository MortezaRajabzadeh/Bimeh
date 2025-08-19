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
        
        // اعمال جستجو
        if ($request->has('search') && !empty($request->input('search'))) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('mobile', 'like', '%' . $search . '%')
                  ->orWhere('username', 'like', '%' . $search . '%');
            });
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

        // تعریف قوانین اعتبارسنجی پایه
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255', 
            'national_code' => 'nullable|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email',
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'create_organization' => 'nullable|boolean',
        ];

        // اضافه کردن قوانین مربوط به سازمان
        if ($request->has('create_organization') && $request->create_organization) {
            $rules = array_merge($rules, [
                'org_name' => 'required|string|max:255',
                'org_type' => 'required|string|in:charity,insurance',
                'org_code' => 'nullable|string|max:255|unique:organizations,code',
                'org_phone' => 'nullable|string|max:255',
                'org_email' => 'nullable|email|max:255',
                'org_address' => 'nullable|string|max:1000',
            ]);
        } else {
            $rules['organization_id'] = 'required|exists:organizations,id';
        }

        $validated = $request->validate($rules, [
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
            'organization_id.required' => 'انتخاب سازمان الزامی است.',
            'organization_id.exists' => 'سازمان انتخاب شده معتبر نیست.',
            'org_name.required' => 'وارد کردن نام سازمان الزامی است.',
            'org_type.required' => 'انتخاب نوع سازمان الزامی است.',
            'org_type.in' => 'نوع سازمان باید خیریه یا بیمه باشد.',
            'org_code.unique' => 'این کد سازمان قبلاً ثبت شده است.',
            'org_email.email' => 'فرمت ایمیل سازمان صحیح نیست.',
        ]);

        try {
            DB::beginTransaction();

            $organizationId = null;

            // اگر کاربر قصد ایجاد سازمان جدید دارد
            if ($request->has('create_organization') && $request->create_organization) {
                $organization = Organization::create([
                    'name' => $validated['org_name'],
                    'type' => $validated['org_type'],
                    'code' => $validated['org_code'] ?? null,
                    'phone' => $validated['org_phone'] ?? null,
                    'email' => $validated['org_email'] ?? null,
                    'address' => $validated['org_address'] ?? null,
                    'is_active' => true,
                ]);
                $organizationId = $organization->id;
            } else {
                $organizationId = $validated['organization_id'];
            }

            // ساخت کاربر
            $user = User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'username' => $validated['username'],
                'email' => $validated['email'] ?? null,
                'mobile' => $validated['mobile'] ?? null,
                'password' => Hash::make($validated['password']),
                'organization_id' => $organizationId,
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
            return back()->withInput()->with('error', 'خطا در ایجاد کاربر: ' . $e->getMessage());
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
    public function update(Request $request, User $user)
    {
        Gate::authorize('edit user');

        // تعریف قوانین اعتبارسنجی پایه
        $rules = [
            'name' => 'required|string|max:255',
            'mobile' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|exists:roles,name',
            'create_organization' => 'nullable|boolean',
        ];

        // اضافه کردن قوانین مربوط به سازمان
        if ($request->has('create_organization') && $request->create_organization) {
            $rules = array_merge($rules, [
                'org_name' => 'required|string|max:255',
                'org_type' => 'required|string|in:charity,insurance',
                'org_address' => 'nullable|string|max:1000',
                'org_description' => 'nullable|string|max:2000',
            ]);
        } else {
            $rules['organization_id'] = 'required|exists:organizations,id';
        }

        $validated = $request->validate($rules, [
            // پیام‌های خطای فارسی
            'name.required' => 'وارد کردن نام کامل الزامی است.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'username.required' => 'وارد کردن نام کاربری الزامی است.',
            'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'رمز عبور و تکرار آن مطابقت ندارند.',
            'role.required' => 'انتخاب نقش الزامی است.',
            'role.exists' => 'نقش انتخاب شده معتبر نیست.',
            'organization_id.required' => 'انتخاب سازمان الزامی است.',
            'organization_id.exists' => 'سازمان انتخاب شده معتبر نیست.',
            'org_name.required' => 'وارد کردن نام سازمان الزامی است.',
            'org_type.required' => 'انتخاب نوع سازمان الزامی است.',
            'org_type.in' => 'نوع سازمان باید خیریه یا بیمه باشد.',
            'org_code.unique' => 'این کد سازمان قبلاً ثبت شده است.',
            'org_email.email' => 'فرمت ایمیل سازمان صحیح نیست.',
        ]);

        try {
            DB::beginTransaction();

            $organizationId = null;

            // اگر کاربر قصد ایجاد سازمان جدید دارد
            if ($request->has('create_organization') && $request->create_organization) {
                $organization = Organization::create([
                    'name' => $validated['org_name'],
                    'type' => $validated['org_type'],
                    'address' => $validated['org_address'] ?? null,
                    'description' => $validated['org_description'] ?? null,
                    'is_active' => true,
                ]);
                $organizationId = $organization->id;
            } else {
                $organizationId = $validated['organization_id'];
            }
        
            // حذف role از validated چون بعداً جداگانه پردازش می‌شود
            $role = $validated['role'] ?? null;
            unset($validated['role']);
            
            // حذف فیلدهای سازمان از validated
            unset($validated['create_organization'], $validated['org_name'], $validated['org_type'], 
                  $validated['org_address'], $validated['org_description']);
            
            // اگر رمز عبور جدید وارد شده باشد، آن را هش می‌کنیم
            if (isset($validated['password']) && !empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }
            
            // به‌روزرسانی user_type و organization_id
            if ($role) {
                $validated['user_type'] = $role;
            }
            $validated['organization_id'] = $organizationId;
            
            $user->update($validated);
            
            // به‌روزرسانی نقش کاربر
            if ($role) {
                $user->syncRoles([$role]);
            }

            DB::commit();
            return redirect()->route('admin.users.index')
                ->with('success', 'اطلاعات کاربر با موفقیت به‌روزرسانی شد.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'خطا در بروزرسانی کاربر: ' . $e->getMessage());
        }
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
