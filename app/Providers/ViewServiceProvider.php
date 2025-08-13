<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // اشتراک گذاری متغیرهای سراسری با همه view ها
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $user = Auth::user();
                
                // متغیرهای مربوط به نقش فعلی
                $view->with('current_user_type', $user->getActiveRole());
                $view->with('admin_acting_as', Session::get('admin_acting_as', 'admin'));
                $view->with('is_impersonating', Session::get('is_impersonating', false));
                
                // اطلاعات کاربر فعلی
                $view->with('current_user', $user->getCurrentActiveUser());
                
                // اطلاعات سازمان (با در نظر گرفتن حالت impersonation)
                $organization = null;
                if (Session::has('current_organization_id')) {
                    $organization = \App\Models\Organization::find(Session::get('current_organization_id'));
                } else {
                    $organization = $user->organization;
                }
                
                $view->with('current_organization', $organization);
                
                // آمار خانواده‌ها برای sidebar (با کش)
                $this->shareStatsWithView($view, $user);
            }
        });
    }
    
    /**
     * اشتراک گذاری آمار خانواده‌ها
     */
    private function shareStatsWithView($view, $user)
    {
        try {
            $cacheKey = 'sidebar_stats_' . $user->id . '_' . $user->getActiveRole();
            
            $stats = cache()->remember($cacheKey, now()->addMinutes(10), function () use ($user) {
                $activeRole = $user->getActiveRole();
                
                if ($activeRole === 'charity') {
                    // آمار خیریه
                    $organizationId = Session::get('current_organization_id') ?? $user->organization_id;
                    
                    if ($organizationId) {
                        $insuredFamilies = \App\Models\Family::where('organization_id', $organizationId)
                            ->where('is_insured', true)
                            ->count();
                        
                        $uninsuredFamilies = \App\Models\Family::where('organization_id', $organizationId)
                            ->where('is_insured', false)
                            ->count();
                        
                        $insuredMembers = \App\Models\Member::whereHas('family', function($q) use ($organizationId) {
                            $q->where('organization_id', $organizationId)
                              ->where('is_insured', true);
                        })->count();
                        
                        $uninsuredMembers = \App\Models\Member::whereHas('family', function($q) use ($organizationId) {
                            $q->where('organization_id', $organizationId)
                              ->where('is_insured', false);
                        })->count();
                        
                        return [
                            'insuredFamilies' => $insuredFamilies,
                            'uninsuredFamilies' => $uninsuredFamilies,
                            'insuredMembers' => $insuredMembers,
                            'uninsuredMembers' => $uninsuredMembers,
                        ];
                    }
                } elseif ($activeRole === 'insurance' || $activeRole === 'admin') {
                    // آمار بیمه/ادمین
                    $insuredFamilies = \App\Models\Family::where('is_insured', true)->count();
                    $uninsuredFamilies = \App\Models\Family::where('is_insured', false)->count();
                    $insuredMembers = \App\Models\Member::whereHas('family', function($q) {
                        $q->where('is_insured', true);
                    })->count();
                    $uninsuredMembers = \App\Models\Member::whereHas('family', function($q) {
                        $q->where('is_insured', false);
                    })->count();
                    
                    return [
                        'insuredFamilies' => $insuredFamilies,
                        'uninsuredFamilies' => $uninsuredFamilies,
                        'insuredMembers' => $insuredMembers,
                        'uninsuredMembers' => $uninsuredMembers,
                    ];
                }
                
                return [
                    'insuredFamilies' => 0,
                    'uninsuredFamilies' => 0,
                    'insuredMembers' => 0,
                    'uninsuredMembers' => 0,
                ];
            });
            
            $view->with($stats);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('خطا در بارگذاری آمار sidebar: ' . $e->getMessage());
            
            // مقادیر پیش‌فرض در صورت خطا
            $view->with([
                'insuredFamilies' => 0,
                'uninsuredFamilies' => 0,
                'insuredMembers' => 0,
                'uninsuredMembers' => 0,
            ]);
        }
    }
}
