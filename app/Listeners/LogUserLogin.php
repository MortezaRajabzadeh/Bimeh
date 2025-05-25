<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Activitylog\Facades\LogActivity;

class LogUserLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        activity('authentication')
            ->causedBy($event->user)
            ->withProperties([
                'user_agent' => request()->userAgent(),
                'ip_address' => request()->ip(),
                'timestamp' => now()->toISOString(),
            ])
            ->event('login')
            ->log($event->user->name . ' وارد سیستم شد');
    }
} 