<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Helpers\DateHelper;
use App\Models\Family;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use App\Livewire\Charity\DashboardStats;
use App\Livewire\Charity\FamilySearch;
use App\Livewire\SidebarToggle;
use App\Livewire\Insurance\DashboardStats as InsuranceDashboardStats;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ثبت کامپوننت‌های Blade
        Blade::component('notification-popup', \App\View\Components\NotificationPopup::class);

        // افزودن دایرکتیو Blade برای تبدیل تاریخ میلادی به شمسی
        Blade::directive('jalali', function ($expression) {
            return "<?php echo \App\Helpers\DateHelper::toJalali($expression); ?>";
        });

        // افزودن دایرکتیو Blade برای نمایش تاریخ نسبی
        Blade::directive('ago', function ($expression) {
            return "<?php echo \App\Helpers\DateHelper::diffForHumans($expression); ?>";
        });
        
        // View Composer برای اطلاعات خانواده‌ها در منوی کناری
        View::composer('layouts.sidebar', function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                
                // بررسی اینکه کاربر از نوع خیریه یا ادمین باشد
                if ($user->user_type === 'charity' || $user->user_type === 'admin') {
                    $charity_id = null;
                    
                    if ($user->user_type === 'charity') {
                        $charity_id = $user->organization_id;
                    }
                    
                    // بررسی مستقیم آمار با SQL برای اطمینان از صحت داده
                    if ($user->user_type === 'admin') {
                        // ادمین تمام آمار خانواده‌ها را می‌بیند
                        $insuredStats = DB::select("
                            SELECT 
                                COUNT(DISTINCT f.id) as family_count,
                                COUNT(DISTINCT m.id) as member_count
                            FROM families f
                            LEFT JOIN members m ON m.family_id = f.id
                            WHERE f.is_insured = 1
                        ");
                        
                        $uninsuredStats = DB::select("
                            SELECT 
                                COUNT(DISTINCT f.id) as family_count,
                                COUNT(DISTINCT m.id) as member_count
                            FROM families f
                            LEFT JOIN members m ON m.family_id = f.id
                            WHERE f.is_insured = 0
                        ");
                    } else {
                        // خیریه فقط آمار خانواده‌های خودش را می‌بیند
                        $insuredStats = DB::select("
                            SELECT 
                                COUNT(DISTINCT f.id) as family_count,
                                COUNT(DISTINCT m.id) as member_count
                            FROM families f
                            LEFT JOIN members m ON m.family_id = f.id
                            WHERE f.charity_id = ? AND f.is_insured = 1
                        ", [$charity_id]);
                        
                        $uninsuredStats = DB::select("
                            SELECT 
                                COUNT(DISTINCT f.id) as family_count,
                                COUNT(DISTINCT m.id) as member_count
                            FROM families f
                            LEFT JOIN members m ON m.family_id = f.id
                            WHERE f.charity_id = ? AND f.is_insured = 0
                        ", [$charity_id]);
                    }
                    
                    $insuredFamilies = isset($insuredStats[0]) ? $insuredStats[0]->family_count : 0;
                    $insuredMembers = isset($insuredStats[0]) ? $insuredStats[0]->member_count : 0;
                    $uninsuredFamilies = isset($uninsuredStats[0]) ? $uninsuredStats[0]->family_count : 0;
                    $uninsuredMembers = isset($uninsuredStats[0]) ? $uninsuredStats[0]->member_count : 0;
                    
                    // ارسال آمار به ویو
                    $view->with([
                        'insuredFamilies' => $insuredFamilies,
                        'insuredMembers' => $insuredMembers,
                        'uninsuredFamilies' => $uninsuredFamilies,
                        'uninsuredMembers' => $uninsuredMembers
                    ]);
                    
                    // چاپ آمار در خروجی برای دیباگ
                    Log::info('Stats for ' . $user->user_type, [
                        'charity_id' => $charity_id,
                        'insured' => $insuredFamilies . ' families, ' . $insuredMembers . ' members',
                        'uninsured' => $uninsuredFamilies . ' families, ' . $uninsuredMembers . ' members',
                    ]);
                }
            }
        });

        // ثبت کامپوننت‌های لایوویر
        Livewire::component('charity.dashboard-stats', DashboardStats::class);
        Livewire::component('insurance.dashboard-stats', InsuranceDashboardStats::class);
        Livewire::component('charity.family-search', FamilySearch::class);
        Livewire::component('sidebar-toggle', SidebarToggle::class);
        Livewire::component('insurance.paid-claims', \App\Livewire\Insurance\PaidClaims::class);
    }
}
