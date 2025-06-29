<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Helpers\DateHelper;
use App\Services\SidebarStatsService;
use App\Models\Family;
use App\Models\Member;
use App\Services\Admin\RoleImpersonationService;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use App\Livewire\Charity\DashboardStats;
use App\Livewire\Charity\FamilySearch;
use App\Livewire\SidebarToggle;
use App\Livewire\Insurance\DashboardStats as InsuranceDashboardStats;
use Illuminate\Support\Facades\Session;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // تنظیم لاگ فعالیت‌ها
        config(['activitylog.enabled' => true]);
        config(['activitylog.subject_returns_soft_deleted_models' => true]);
        
        // تنظیم سطح دسترسی
        config(['permission.register_permission_check_method' => true]);
        config(['permission.teams' => false]);

        // ثبت DateHelper به عنوان یک سرویس
        $this->app->bind('date-helper', function () {
            return new DateHelper();
        });
        
        // ثبت سرویس تغییر نقش ادمین به صورت singleton
        $this->app->singleton(RoleImpersonationService::class, function ($app) {
            return new RoleImpersonationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ثبت کامپوننت‌های Blade
        Blade::component('notification-popup', \App\View\Components\NotificationPopup::class);

        // افزودن دایرکتیوهای Blade
        $this->registerBladeDirectives();
        
        // View Composer ساده
        View::composer('layouts.sidebar', function ($view) {
            try {
                $statsService = app(SidebarStatsService::class);
                $stats = $statsService->getStatsForUser();
                $view->with($stats);
            } catch (\Exception $e) {
                // Log the error and provide default values
                \Log::error('Sidebar stats error: ' . $e->getMessage());
                $view->with([
                    'insuredFamilies' => 0,
                    'insuredMembers' => 0,
                    'uninsuredFamilies' => 0,
                    'uninsuredMembers' => 0,
                    'current_user_type' => auth()->user()->user_type ?? 'guest'
                ]);
            }
        });

        // ثبت کامپوننت‌های لایوویر
        $this->registerLivewireComponents();
    }

    private function registerBladeDirectives()
    {
        Blade::directive('jalali', function ($expression) {
            return "<?php echo \App\Helpers\DateHelper::toJalali($expression); ?>";
        });

        Blade::directive('ago', function ($expression) {
            return "<?php echo \App\Helpers\DateHelper::diffForHumans($expression); ?>";
        });
    }

    private function registerLivewireComponents()
    {
        Livewire::component('charity.dashboard-stats', DashboardStats::class);
        Livewire::component('insurance.dashboard-stats', InsuranceDashboardStats::class);
        Livewire::component('charity.family-search', FamilySearch::class);
        Livewire::component('sidebar-toggle', SidebarToggle::class);
        Livewire::component('insurance.paid-claims', \App\Livewire\Insurance\PaidClaims::class);
    }
}
