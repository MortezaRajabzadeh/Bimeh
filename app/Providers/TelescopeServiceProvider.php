<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Laravel\Telescope\TelescopeApplicationServiceProvider;

class TelescopeServiceProvider extends TelescopeApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // فعال‌سازی Telescope فقط در محیط local یا زمانی که TELESCOPE_ENABLED=true است
        $telescopeEnabled = $this->app->environment('local') || config('telescope.enabled');
        
        if (!$telescopeEnabled) {
            return;
        }
        
        // Telescope::night();

        $this->hideSensitiveRequestDetails();

        $isLocal = $this->app->environment('local');

        Telescope::filter(function (IncomingEntry $entry) use ($isLocal) {
            return $isLocal ||
                   $entry->isReportableException() ||
                   $entry->isFailedRequest() ||
                   $entry->isFailedJob() ||
                   $entry->isScheduledTask() ||
                   $entry->hasMonitoredTag();
        });
    }

    /**
     * Prevent sensitive request details from being logged by Telescope.
     */
    protected function hideSensitiveRequestDetails(): void
    {
        if ($this->app->environment('local')) {
            return;
        }

        Telescope::hideRequestParameters(['_token']);

        Telescope::hideRequestHeaders([
            'cookie',
            'x-csrf-token',
            'x-xsrf-token',
        ]);
    }

    /**
     * Register the Telescope gate.
     *
     * This gate determines who can access Telescope in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewTelescope', function ($user) {
            // فقط در محیط local یا با تنظیم TELESCOPE_ENABLED=true اجازه دسترسی داده می‌شود
            if ($this->app->environment('local')) {
                return true;
            }
            
            // در محیط‌های دیگر، فقط کاربران مشخص شده اجازه دسترسی دارند
            return in_array($user->email, [
                // لیست ایمیل‌های مجاز
            ]);
        });
    }
}
