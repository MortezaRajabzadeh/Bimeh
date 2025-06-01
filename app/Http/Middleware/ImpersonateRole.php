<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ImpersonateRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // بررسی وجود نقش موقت در سشن
        if ($request->session()->has('impersonate_as') && auth()->check() && auth()->user()->hasRole('admin')) {
            // نقش موقت را از سشن دریافت می‌کنیم
            $impersonatedRole = $request->session()->get('impersonate_as');
            
            // بررسی اعتبار نقش
            if (in_array($impersonatedRole, ['charity', 'insurance'])) {
                // اضافه کردن متغیر به view برای نمایش بنر
                view()->share('impersonating', [
                    'role' => $impersonatedRole,
                    'display_name' => $impersonatedRole === 'charity' ? 'خیریه' : 'بیمه'
                ]);
            }
        }

        return $next($request);
    }
}