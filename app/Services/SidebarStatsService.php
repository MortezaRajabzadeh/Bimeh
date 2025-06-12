<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

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
}