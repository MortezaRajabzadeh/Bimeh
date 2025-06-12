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
        if ($user->hasRole('admin')) {
            return $next($request);
        }

        // بررسی نوع کاربر و رول مربوطه
        switch ($userType) {
            case 'admin':
                if (!$user->hasRole('admin')) {
                    abort(403, 'شما به این بخش دسترسی ندارید. فقط مدیران سیستم می‌توانند وارد شوند.');
                }
                break;

            case 'charity':
                if (!$user->hasRole('charity') && !$user->hasRole('admin')) {
                    abort(403, 'دسترسی به این بخش فقط برای کاربران خیریه مجاز است.');
                }
                break;

            case 'insurance':
                if (!$user->hasRole('insurance') && !$user->hasRole('admin')) {
                    abort(403, 'دسترسی به این بخش فقط برای کاربران بیمه مجاز است.');
                }
                break;

            default:
                abort(403, 'نوع کاربری نامعتبر است.');
        }

        return $next($request);
    }
} 
