<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Family;
use App\Policies\FamilyPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Family::class => FamilyPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // تعریف Gate ها برای دسترسی‌ها
        // این دسترسی‌ها از سیستم مجوزهای Spatie استفاده می‌کنند

        // سازمان‌ها
        Gate::define('view organizations', function ($user) {
            return $user->hasPermissionTo('view organizations');
        });
        
        Gate::define('create organization', function ($user) {
            return $user->hasPermissionTo('create organization');
        });
        
        Gate::define('edit organization', function ($user) {
            return $user->hasPermissionTo('edit organization');
        });
        
        Gate::define('delete organization', function ($user) {
            return $user->hasPermissionTo('delete organization');
        });
        
        // مناطق
        Gate::define('view regions', function ($user) {
            return $user->hasPermissionTo('view regions');
        });
        
        Gate::define('create region', function ($user) {
            return $user->hasPermissionTo('create region');
        });
        
        Gate::define('edit region', function ($user) {
            return $user->hasPermissionTo('edit region');
        });
        
        Gate::define('delete region', function ($user) {
            return $user->hasPermissionTo('delete region');
        });
        
        // کاربران
        Gate::define('view users', function ($user) {
            return $user->hasPermissionTo('view users');
        });
        
        Gate::define('create user', function ($user) {
            return $user->hasPermissionTo('create user');
        });
        
        Gate::define('edit user', function ($user) {
            return $user->hasPermissionTo('edit user');
        });
        
        Gate::define('delete user', function ($user) {
            return $user->hasPermissionTo('delete user');
        });
        
        // خانواده‌ها
        Gate::define('view families', function ($user) {
            return $user->hasPermissionTo('view families');
        });
        
        Gate::define('create family', function ($user) {
            return $user->hasPermissionTo('create family');
        });
        
        Gate::define('edit family', function ($user) {
            return $user->hasPermissionTo('edit family');
        });
        
        Gate::define('delete family', function ($user) {
            return $user->hasPermissionTo('delete family');
        });
        
        Gate::define('change family status', function ($user) {
            return $user->hasPermissionTo('change family status');
        });
        
        Gate::define('verify-family', function ($user) {
            return $user->hasPermissionTo('verify family');
        });
        
        // اعضای خانواده
        Gate::define('view members', function ($user) {
            return $user->hasPermissionTo('view members');
        });
        
        Gate::define('create member', function ($user) {
            return $user->hasPermissionTo('create member');
        });
        
        Gate::define('edit member', function ($user) {
            return $user->hasPermissionTo('edit member');
        });
        
        Gate::define('delete member', function ($user) {
            return $user->hasPermissionTo('delete member');
        });
        
        // گزارش‌ها
        Gate::define('view reports', function ($user) {
            return $user->hasPermissionTo('view reports');
        });
        
        Gate::define('export reports', function ($user) {
            return $user->hasPermissionTo('export reports');
        });
        
        // لاگ فعالیت‌ها
        Gate::define('view activity logs', function ($user) {
            return $user->hasPermissionTo('view activity logs');
        });
        
        // داشبورد
        Gate::define('view dashboard', function ($user) {
            return $user->hasPermissionTo('view dashboard');
        });
    }
} 
