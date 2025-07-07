<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class HandleUserImpersonation
{
    public function handle(Request $request, Closure $next)
    {
        // اگر ادمین در حال impersonate کردن کاربر دیگری است
        if (Session::has('impersonated_user_id') && Session::has('original_admin_id')) {
            $impersonatedUser = User::find(Session::get('impersonated_user_id'));
            $originalAdmin = User::find(Session::get('original_admin_id'));
            
            if ($impersonatedUser && $originalAdmin && Auth::id() === $originalAdmin->id) {
                // تنظیم کاربر فعلی به کاربر impersonate شده
                $request->setUserResolver(function () use ($impersonatedUser) {
                    return $impersonatedUser;
                });
            }
        }
        
        return $next($request);
    }
}