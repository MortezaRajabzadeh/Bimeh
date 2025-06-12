<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Spatie\Activitylog\Facades\LogActivity;

class LogUserLogout
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
    public function handle(Logout $event): void
    {
        if ($event->user) {
            activity('authentication')
                ->causedBy($event->user)
                ->withProperties([
                    'user_agent' => request()->userAgent(),
                    'ip_address' => request()->ip(),
                    'timestamp' => now()->toISOString(),
                ])
                ->event('logout')
                ->log($event->user->name . ' از سیستم خارج شد');
        }
    }
} 
