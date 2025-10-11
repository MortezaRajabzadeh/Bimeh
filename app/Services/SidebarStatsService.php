<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class SidebarStatsService
{
    public function getStatsForUser($user = null)
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            return $this->getDefaultStats();
        }
        
        $cacheKey = "sidebar_stats_{$user->id}_" . Session::get('current_user_type', $user->user_type);
        
        return Cache::remember($cacheKey, 300, function () use ($user) {
            return $this->calculateStats($user);
        });
    }
    
    private function calculateStats($user)
    {
        $userType = Session::get('current_user_type', $user->user_type);
        
        switch ($userType) {
            case 'charity':
            case 'admin':
                return $this->getCharityAdminStats($user, $userType);
            case 'insurance':
                return $this->getInsuranceStats($user);
            default:
                return $this->getDefaultStats();
        }
    }
    
    private function getCharityAdminStats($user, $userType)
    {
        $charity_id = null;
        $isAdminImpersonating = false;
        
        // بررسی اگر ادمین در حال تقلید نقش است
        if (Session::has('original_admin_roles') && Session::has('is_impersonating') && Session::get('is_impersonating') === true) {
            $isAdminImpersonating = true;
        } else if ($userType === 'charity') {
            // فقط در حالت خیریه واقعی، فیلتر charity_id اعمال شود
            $charity_id = $user->organization_id;
        }
        
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
        
        return [
            'insuredFamilies' => isset($insuredStats[0]) ? $insuredStats[0]->family_count : 0,
            'insuredMembers' => isset($insuredStats[0]) ? $insuredStats[0]->member_count : 0,
            'uninsuredFamilies' => isset($uninsuredStats[0]) ? $uninsuredStats[0]->family_count : 0,
            'uninsuredMembers' => isset($uninsuredStats[0]) ? $uninsuredStats[0]->member_count : 0,
            'current_user_type' => $userType
        ];
    }
    
    private function getInsuranceStats($user)
    {
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
        
        return [
            'insuredFamilies' => isset($insuredStats[0]) ? $insuredStats[0]->family_count : 0,
            'insuredMembers' => isset($insuredStats[0]) ? $insuredStats[0]->member_count : 0,
            'uninsuredFamilies' => isset($uninsuredStats[0]) ? $uninsuredStats[0]->family_count : 0,
            'uninsuredMembers' => isset($uninsuredStats[0]) ? $uninsuredStats[0]->member_count : 0,
            'current_user_type' => 'insurance'
        ];
    }
    
    private function getDefaultStats()
    {
        return [
            'insuredFamilies' => 0,
            'insuredMembers' => 0,
            'uninsuredFamilies' => 0,
            'uninsuredMembers' => 0,
            'current_user_type' => null
        ];
    }
    
    /**
     * پاک کردن کش آمار sidebar
     * 
     * @param int|null $userId شناسه کاربر خاص برای پاک کردن کش آن کاربر
     * @param string|null $userType نوع کاربر ('admin', 'charity', 'insurance') برای پاک کردن کش همه کاربران آن نوع
     * @param bool $clearAll اگر true باشد، تمام کش‌های sidebar پاک می‌شوند
     * @return int تعداد کلیدهای پاک شده
     */
    public function clearStatsCache(?int $userId = null, ?string $userType = null, bool $clearAll = false): int
    {
        try {
            $clearedCount = 0;
            $userTypes = ['admin', 'charity', 'insurance'];
            
            // سناریو 1: پاک کردن کش یک کاربر خاص
            if ($userId !== null) {
                foreach ($userTypes as $type) {
                    $cacheKey = "sidebar_stats_{$userId}_{$type}";
                    if (Cache::forget($cacheKey)) {
                        $clearedCount++;
                    }
                }
                
                Log::info('Sidebar stats cache cleared', [
                    'type' => 'user',
                    'user_id' => $userId,
                    'cleared_count' => $clearedCount
                ]);
                
                return $clearedCount;
            }
            
            // سناریو 2: پاک کردن کش یک نوع کاربر
            if ($userType !== null) {
                if (!in_array($userType, $userTypes)) {
                    Log::warning('Invalid user type provided for cache clearing', ['user_type' => $userType]);
                    return 0;
                }
                
                $userIds = DB::table('users')
                    ->where('user_type', $userType)
                    ->pluck('id');
                
                foreach ($userIds as $id) {
                    $cacheKey = "sidebar_stats_{$id}_{$userType}";
                    if (Cache::forget($cacheKey)) {
                        $clearedCount++;
                    }
                }
                
                Log::info('Sidebar stats cache cleared', [
                    'type' => 'user_type',
                    'user_type' => $userType,
                    'cleared_count' => $clearedCount
                ]);
                
                return $clearedCount;
            }
            
            // سناریو 3: پاک کردن تمام کش‌های sidebar
            if ($clearAll) {
                $cachePrefix = config('cache.prefix', '');
                $pattern = $cachePrefix ? "{$cachePrefix}:sidebar_stats_%" : "sidebar_stats_%";
                
                $clearedCount = DB::table('cache')
                    ->where('key', 'like', $pattern)
                    ->delete();
                
                Log::info('Sidebar stats cache cleared', [
                    'type' => 'all',
                    'cleared_count' => $clearedCount,
                    'pattern' => $pattern
                ]);
                
                return $clearedCount;
            }
            
            // اگر هیچ پارامتری ارسال نشده، هیچ کاری انجام نمی‌دهیم
            Log::warning('clearStatsCache called without parameters');
            return 0;
            
        } catch (\Exception $e) {
            Log::error('Error clearing sidebar stats cache', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId,
                'user_type' => $userType,
                'clear_all' => $clearAll
            ]);
            
            throw $e;
        }
    }
}
