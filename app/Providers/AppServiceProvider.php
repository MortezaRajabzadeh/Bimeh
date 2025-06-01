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
                
                // متغیرهای پیش‌فرض
                $insuredFamilies = 0;
                $insuredMembers = 0;
                $uninsuredFamilies = 0;
                $uninsuredMembers = 0;
                
                // بررسی user_type فعلی (از سشن یا مدل کاربر)
                $userType = Session::has('current_user_type') ? Session::get('current_user_type') : $user->user_type;
                
                // بررسی اینکه کاربر از نوع خیریه یا ادمین باشد
                if ($userType === 'charity' || $userType === 'admin') {
                    $charity_id = null;
                    $isAdminImpersonating = false;
                    
                    // بررسی اگر ادمین در حال تقلید نقش است
                    if (Session::has('original_admin_roles') && Session::has('is_impersonating') && Session::get('is_impersonating') === true) {
                        $isAdminImpersonating = true;
                        Log::info('ادمین در حال تقلید نقش است - نمایش آمار کلی');
                    } else if ($userType === 'charity') {
                        // فقط در حالت خیریه واقعی، فیلتر charity_id اعمال شود
                        $charity_id = $user->organization_id;
                        Log::info('کاربر خیریه واقعی است - فیلتر بر اساس charity_id: ' . $charity_id);
                    }
                    
                    // بررسی مستقیم آمار با SQL برای اطمینان از صحت داده
                    if ($userType === 'admin' || $isAdminImpersonating) {
                        // ادمین و ادمین در حال تقلید نقش خیریه، تمام آمار خانواده‌ها را می‌بینند
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
                        
                        Log::info('آمار کلی نمایش داده می‌شود: insured=' . ($insuredStats[0]->family_count ?? 0) . ', uninsured=' . ($uninsuredStats[0]->family_count ?? 0));
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
                        
                        Log::info('آمار فیلتر شده خیریه: charity_id=' . $charity_id . ', insured=' . ($insuredStats[0]->family_count ?? 0) . ', uninsured=' . ($uninsuredStats[0]->family_count ?? 0));
                    }
                    
                    $insuredFamilies = isset($insuredStats[0]) ? $insuredStats[0]->family_count : 0;
                    $insuredMembers = isset($insuredStats[0]) ? $insuredStats[0]->member_count : 0;
                    $uninsuredFamilies = isset($uninsuredStats[0]) ? $uninsuredStats[0]->family_count : 0;
                    $uninsuredMembers = isset($uninsuredStats[0]) ? $uninsuredStats[0]->member_count : 0;
                }
                
                // بررسی اینکه کاربر از نوع بیمه باشد
                if ($userType === 'insurance') {
                    $insurance_id = $user->organization_id;
                    
                    // آمار خانواده‌های بیمه شده (کل خانواده‌هایی که بیمه دارند)
                    $insuredStats = DB::select("
                        SELECT 
                            COUNT(DISTINCT f.id) as family_count,
                            COUNT(DISTINCT m.id) as member_count
                        FROM families f
                        LEFT JOIN members m ON m.family_id = f.id
                        WHERE f.is_insured = 1
                    ");
                    
                    // آمار خانواده‌های بدون بیمه (کل خانواده‌هایی که بیمه ندارند)
                    $uninsuredStats = DB::select("
                        SELECT 
                            COUNT(DISTINCT f.id) as family_count,
                            COUNT(DISTINCT m.id) as member_count
                        FROM families f
                        LEFT JOIN members m ON m.family_id = f.id
                        WHERE f.is_insured = 0 OR f.is_insured IS NULL
                    ");
                    
                    $insuredFamilies = isset($insuredStats[0]) ? $insuredStats[0]->family_count : 0;
                    $insuredMembers = isset($insuredStats[0]) ? $insuredStats[0]->member_count : 0;
                    $uninsuredFamilies = isset($uninsuredStats[0]) ? $uninsuredStats[0]->family_count : 0;
                    $uninsuredMembers = isset($uninsuredStats[0]) ? $uninsuredStats[0]->member_count : 0;
                }
                
                // ارسال آمار به ویو
                $view->with([
                    'insuredFamilies' => $insuredFamilies,
                    'insuredMembers' => $insuredMembers,
                    'uninsuredFamilies' => $uninsuredFamilies,
                    'uninsuredMembers' => $uninsuredMembers,
                    'current_user_type' => $userType // اضافه کردن user_type فعلی به ویو
                ]);
            } else {
                // اگر کاربر لاگین نکرده، مقادیر پیش‌فرض
                $view->with([
                    'insuredFamilies' => 0,
                    'insuredMembers' => 0,
                    'uninsuredFamilies' => 0,
                    'uninsuredMembers' => 0
                ]);
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
