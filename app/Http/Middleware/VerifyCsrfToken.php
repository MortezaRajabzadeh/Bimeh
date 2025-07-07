<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // در صورت نیاز به استثنا کردن مسیرها از بررسی CSRF، آنها را اینجا اضافه کنید
        // مثال: 'api/*',
    ];
}
