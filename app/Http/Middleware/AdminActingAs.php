<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Admin\RoleImpersonationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminActingAs
{
    protected $roleService;
    
    /**
     * ایجاد نمونه میدلویر جدید
     */
    public function __construct(RoleImpersonationService $roleService)
    {
        $this->roleService = $roleService;
    }
    
    /**
     * میدلویر برای اعمال نقش موقت ادمین در بخش‌های مختلف
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('admin') && Session::has('admin_acting_as')) {
            $actingAs = Session::get('admin_acting_as');
            
            // اگر در حال impersonation هستیم، اطمینان حاصل کنیم که user_type هم به درستی تنظیم شده است
            if (Session::has('is_impersonating') && Session::get('is_impersonating') && $actingAs !== 'admin') {
                // تنظیم user_type در session و مدل user
                Session::put('current_user_type', $actingAs);
                // ذخیره نقش اصلی در سشن برای استفاده در AppServiceProvider
                if ($user->hasRole('admin')) {
                    Session::put('original_admin_roles', ['admin']);
                    Session::put('is_impersonating', true);
                }
                $user->user_type = $actingAs;
                
                // دیگر نیازی به تنظیم organization_id نیست چون accessor در مدل کاربر آن را مدیریت می‌کند
                
                // چون ممکن است کش‌های Auth::user() قبلاً ساخته شده باشند، آن را بازنشانی می‌کنیم
                Auth::setUser($user);
                
                Log::info('AdminActingAs middleware: user_type = ' . $actingAs . ', organization_id = ' . $user->organization_id);
            } else if ($actingAs === 'admin' && Session::has('original_user_type')) {
                // بازگرداندن user_type اصلی در صورت برگشت به حالت ادمین
                Session::put('current_user_type', Session::get('original_user_type'));
                $user->user_type = Session::get('original_user_type');
                
                // بازنشانی کش Auth::user()
                Auth::setUser($user);
                
                Log::info('AdminActingAs middleware: بازگشت به ادمین، user_type = ' . $user->user_type . ', organization_id = ' . $user->organization_id);
            }
            
            // تنظیم متغیرهای view برای UI
            view()->share('admin_acting_as', $actingAs);
            view()->share('is_impersonating', $this->roleService->isImpersonating($user));
            view()->share('original_role', 'admin');
            view()->share('current_role', $this->roleService->getCurrentRole($user));
            
            // تنظیم کوکی نقش فعال برای JavaScript
            cookie()->queue('active_role', $actingAs, 120);
        }

        return $next($request);
    }
} 