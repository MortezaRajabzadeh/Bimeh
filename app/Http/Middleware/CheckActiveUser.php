<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
;

class CheckActiveUser
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && !auth()->user()->is_active) {
            abort(403, 'حساب شما غیرفعال است.');
        }

        return $next($request);
    }
}
