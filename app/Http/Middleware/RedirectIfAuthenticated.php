<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                // بر اساس نوع کاربر، به داشبورد مربوطه هدایت می‌شود
                $user = Auth::guard($guard)->user();
                
                if ($user->user_type === 'admin') {
                    return redirect()->route('admin.dashboard');
                } elseif ($user->user_type === 'charity') {
                    return redirect()->route('charity.dashboard');
                } elseif ($user->user_type === 'insurance') {
                    return redirect()->route('insurance.dashboard');
                }
                
                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
} 
