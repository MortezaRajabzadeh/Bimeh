<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Session;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Organization;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RoleImpersonationService
{
    // کلید کش برای ذخیره تنظیمات impersonation
    protected $cacheKeyPrefix = 'admin_impersonation_';

    /**
     * تغییر نقش کاربر ادمین به نقش مورد نظر
     *
     * @param \App\Models\User $user
     * @param string $roleName
     * @return bool
     */
    public function impersonate(User $user, string $roleName): bool
    {
        if (!$user->hasRole('admin')) {
            return false;
        }
        
        // ذخیره نقش‌های اصلی ادمین اگر اولین بار است
        if (!Session::has('original_admin_roles')) {
            Session::put('original_admin_roles', $user->getRoleNames()->toArray());
            // ذخیره user_type اصلی
            Session::put('original_user_type', $user->user_type);
            // ذخیره organization_id اصلی
            Session::put('original_organization_id', $user->getOriginal('organization_id'));
        }
        
        // ذخیره نقش موقت در سشن
        Session::put('admin_acting_as', $roleName);
        Session::put('is_impersonating', true);
        
        if ($roleName !== 'admin') {
            // حذف نقش‌های موقت قبلی
            foreach(['charity', 'insurance'] as $role) {
                if ($user->hasRole($role) && 
                    Session::has('original_admin_roles') && 
                    !in_array($role, Session::get('original_admin_roles'))) {
                    $user->removeRole($role);
                }
            }
            
            // اضافه کردن نقش جدید
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
            }
            
            // تغییر موقت user_type کاربر و ذخیره در سشن
            $user->user_type = $roleName;
            Session::put('current_user_type', $roleName);
            
            // تنظیم organization_id موقت برای کاربر
            if ($roleName === 'charity' || $roleName === 'insurance') {
                // پیدا کردن اولین سازمان فعال از نوع مربوطه
                $organization = Organization::where('type', $roleName)
                    ->where('is_active', true)
                    ->first();
                
                if ($organization) {
                    // فقط در سشن ذخیره می‌کنیم، accessor در مدل خودش مقدار را برمی‌گرداند
                    Session::put('current_organization_id', $organization->id);
                    
                    // اطمینان از ذخیره وضعیت impersonation
                    // Session::put('is_impersonating', true);
                    
                    // ذخیره در کش با TTL مناسب (24 ساعت)
                    $cacheKey = $this->getCacheKey($user->id);
                    Cache::put($cacheKey, [
                        'role' => $roleName,
                        'organization_id' => $organization->id,
                        'user_type' => $roleName
                    ], now()->addHours(24));
                    
                    Log::info('تنظیم سازمان موقت: ' . $organization->id . ' برای کاربر ' . $user->id . ' با نقش ' . $roleName);
                } else {
                    Log::warning('هیچ سازمان فعالی از نوع ' . $roleName . ' یافت نشد.');
                }
            }
            
            // بازنشانی کش Auth::user()
            Auth::setUser($user);
            
            return true;
        } else {
            // بازگشت به نقش ادمین
            return $this->stopImpersonating($user);
        }
    }
    
    /**
     * توقف عملیات impersonation و بازگشت به نقش‌های اصلی
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function stopImpersonating(User $user): bool
    {
        if (Session::has('original_admin_roles')) {
            // حذف همه نقش‌های موقت
            $user->syncRoles([]);
            
            // بازگرداندن نقش‌های اصلی
            foreach (Session::get('original_admin_roles') as $roleName) {
                $user->assignRole($roleName);
            }
            
            // بازگرداندن user_type اصلی
            if (Session::has('original_user_type')) {
                $user->user_type = Session::get('original_user_type');
                Session::put('current_user_type', Session::get('original_user_type'));
            }
            
            // حذف organization_id موقت از سشن
            Session::forget('current_organization_id');
            
            // حذف اطلاعات از کش
            $cacheKey = $this->getCacheKey($user->id);
            Cache::forget($cacheKey);
            
            // بازنشانی کش Auth::user()
            Auth::setUser($user);
            
            // حذف نشانه‌های وضعیت از سشن
            Session::put('admin_acting_as', 'admin');
            Session::forget('is_impersonating');
            
            return true;
        }
        
        return false;
    }
    
    /**
     * بررسی آیا کاربر در حال impersonation است
     *
     * @param \App\Models\User $user
     * @return bool
     */
    public function isImpersonating(User $user): bool
    {
        return Session::has('is_impersonating') && 
               Session::get('is_impersonating') === true &&
               Session::has('original_admin_roles') &&
               in_array('admin', Session::get('original_admin_roles'));
    }
    
    /**
     * دریافت نقش فعلی کاربر
     *
     * @param \App\Models\User $user
     * @return string
     */
    public function getCurrentRole(User $user): string
    {
        if ($this->isImpersonating($user) && Session::has('admin_acting_as')) {
            return Session::get('admin_acting_as');
        }
        
        // تشخیص نقش اصلی کاربر
        if ($user->hasRole('admin')) {
            return 'admin';
        } elseif ($user->hasRole('charity')) {
            return 'charity';
        } elseif ($user->hasRole('insurance')) {
            return 'insurance';
        }
        
        return '';
    }
    
    /**
     * تولید کلید کش برای کاربر
     *
     * @param int $userId
     * @return string
     */
    protected function getCacheKey(int $userId): string
    {
        return $this->cacheKeyPrefix . $userId;
    }
} 