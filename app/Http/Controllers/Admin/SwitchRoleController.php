<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Services\Admin\RoleImpersonationService;

class SwitchRoleController extends Controller
{
    private $roleService;
    
    /**
     * ایجاد نمونه کنترلر جدید
     */
    public function __construct(RoleImpersonationService $roleService)
    {
        $this->roleService = $roleService;
    }
    
    /**
     * ذخیره و تغییر نقش موقت ادمین
     */
    public function store(Request $request)
    {
        // اعتبارسنجی ورودی‌ها
        $request->validate([
            'role' => 'required|in:admin,charity,insurance',
        ]);

        $role = $request->input('role');
        $user = $request->user();

        // تغییر نقش با استفاده از سرویس
        $this->roleService->impersonate($user, $role);
        
        // تعیین مقصد ریدایرکت بر اساس نقش انتخاب شده
        $redirectRoute = match ($role) {
            'charity' => 'charity.dashboard',
            'insurance' => 'insurance.dashboard',
            default => 'admin.dashboard',
        };
        
        // پیام موفقیت‌آمیز
        if ($role === 'admin') {
            return redirect()->route($redirectRoute)->with('toast', [
                'type' => 'success', 
                'message' => 'به پنل ادمین بازگشتید.'
            ]);
        } else {
            $roleName = $role === 'charity' ? 'خیریه' : 'بیمه';
            return redirect()->route($redirectRoute)->with('toast', [
                'type' => 'success', 
                'message' => "نمایش به عنوان {$roleName} فعال شد."
            ]);
        }
    }
} 