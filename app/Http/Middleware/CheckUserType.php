<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserType
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $userType): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // اگر کاربر ادمین باشد، به تمام بخش‌ها دسترسی دارد
        if ($user->isAdmin()) {
            return $next($request);
        }

        // بررسی نوع کاربر برای سایر کاربران
        if ($userType === 'admin' && !$user->isAdmin()) {
            abort(403, 'شما به این بخش دسترسی ندارید.');
        }

        if ($userType === 'charity' && !$user->isCharity()) {
            abort(403, 'دسترسی به این بخش فقط برای کاربران خیریه مجاز است.');
        }

        if ($userType === 'insurance' && !$user->isInsurance()) {
            abort(403, 'دسترسی به این بخش فقط برای کاربران بیمه مجاز است.');
        }

        return $next($request);
    }
} 