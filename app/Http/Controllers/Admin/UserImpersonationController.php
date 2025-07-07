<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\ActivityLog;

class UserImpersonationController extends Controller
{
    /**
     * شروع impersonation کاربر خاص
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $adminUser = Auth::user();
        $targetUser = User::findOrFail($request->user_id);
        
        // فقط ادمین می‌تواند impersonate کند
        if (!$adminUser->hasRole('admin')) {
            return redirect()->back()->with('toast', [
                'type' => 'error',
                'message' => 'شما مجوز این عملیات را ندارید.'
            ]);
        }

        // ذخیره اطلاعات ادمین اصلی
        if (!Session::has('original_admin_id')) {
            Session::put('original_admin_id', $adminUser->id);
        }
        
        // ذخیره شناسه کاربر مورد نظر
        Session::put('impersonated_user_id', $targetUser->id);
        
        // ثبت لاگ فعالیت
        ActivityLog::create([
            'log_name' => 'user_impersonation',
            'description' => "ادمین {$adminUser->name} وارد حساب کاربر {$targetUser->name} شد",
            'subject_type' => User::class,
            'subject_id' => $targetUser->id,
            'causer_type' => User::class,
            'causer_id' => $adminUser->id,
            'properties' => [
                'admin_user' => $adminUser->toArray(),
                'target_user' => $targetUser->toArray(),
                'timestamp' => now()
            ]
        ]);

        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => "وارد حساب {$targetUser->name} شدید."
        ]);
    }

    /**
     * توقف impersonation و بازگشت به حساب ادمین
     */
    public function stop()
    {
        $adminId = Session::get('original_admin_id');
        $impersonatedUserId = Session::get('impersonated_user_id');
        
        if ($adminId && $impersonatedUserId) {
            $adminUser = User::find($adminId);
            $impersonatedUser = User::find($impersonatedUserId);
            
            // ثبت لاگ خروج
            ActivityLog::create([
                'log_name' => 'user_impersonation',
                'description' => "ادمین {$adminUser->name} از حساب کاربر {$impersonatedUser->name} خارج شد",
                'subject_type' => User::class,
                'subject_id' => $impersonatedUser->id,
                'causer_type' => User::class,
                'causer_id' => $adminUser->id,
                'properties' => [
                    'admin_user' => $adminUser->toArray(),
                    'target_user' => $impersonatedUser->toArray(),
                    'timestamp' => now()
                ]
            ]);
        }
        
        // پاک کردن session
        Session::forget(['original_admin_id', 'impersonated_user_id']);
        
        return redirect()->back()->with('toast', [
            'type' => 'success',
            'message' => 'به حساب اصلی خود بازگشتید.'
        ]);
    }
}