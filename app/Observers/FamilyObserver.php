<?php

namespace App\Observers;

use App\Models\Family;
use App\Models\FamilyStatusLog;
use App\Enums\InsuranceWizardStep;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FamilyObserver
{
    /**
     * Handle the Family "updated" event.
     * 
     * این متد زمانی فراخوانی می‌شود که یک خانواده ویرایش می‌شود.
     * اگر کاربر ادمین باشد و خانواده در مرحله بعد از PENDING باشد،
     * یک لاگ در FamilyStatusLog ثبت می‌شود.
     *
     * @param  \App\Models\Family  $family
     * @return void
     */
    public function updated(Family $family)
    {
        // بررسی اینکه آیا کاربری لاگین کرده است
        if (!Auth::check()) {
            return;
        }

        $user = Auth::user();

        // فقط برای ادمین‌ها لاگ ثبت می‌کنیم
        if (!$user->isAdmin()) {
            return;
        }

        // بررسی وضعیت wizard - فقط برای خانواده‌هایی که از PENDING گذشته‌اند
        $wizardStatus = $family->wizard_status;
        if (!$wizardStatus || $wizardStatus === InsuranceWizardStep::PENDING->value) {
            return;
        }

        // بررسی اینکه آیا فیلدهای مهم تغییر کرده‌اند
        $importantFields = [
            'family_code', 'province_id', 'city_id', 'district_id', 'region_id',
            'head_id', 'charity_id', 'address', 'postal_code', 'housing_status',
            'acceptance_criteria', 'additional_info'
        ];

        $changedFields = [];
        foreach ($importantFields as $field) {
            if ($family->wasChanged($field)) {
                $changedFields[] = $field;
            }
        }

        // اگر هیچ فیلد مهمی تغییر نکرده، لاگ ثبت نمی‌کنیم
        if (empty($changedFields)) {
            return;
        }

        // ثبت لاگ در FamilyStatusLog
        try {
            $comments = 'ویرایش خانواده توسط ادمین - فیلدهای تغییر یافته: ' . implode(', ', $changedFields);
            
            // اطلاعات تغییرات را در extra_data ذخیره می‌کنیم
            $extraData = [];
            foreach ($changedFields as $field) {
                $extraData[$field] = [
                    'old' => $family->getOriginal($field),
                    'new' => $family->getAttribute($field)
                ];
            }

            FamilyStatusLog::create([
                'family_id' => $family->id,
                'user_id' => $user->id,
                'from_status' => $wizardStatus,
                'to_status' => $wizardStatus, // وضعیت تغییر نکرده، فقط اطلاعات ویرایش شده
                'comments' => $comments,
                'extra_data' => $extraData,
            ]);

            Log::info('✅ Admin edit logged for family ' . $family->id . ' by user ' . $user->id);
        } catch (\Exception $e) {
            Log::error('❌ Error logging admin edit for family ' . $family->id . ': ' . $e->getMessage());
            // ادامه اجرا حتی اگر لاگ ثبت نشد
        }
        
        // 🔄 تشخیص تغییرات فیلد is_insured و پاک کردن کش sidebar
        if ($family->wasChanged('is_insured')) {
            try {
                Log::info('🔄 is_insured field changed, clearing sidebar cache', [
                    'family_id' => $family->id,
                    'old_value' => $family->getOriginal('is_insured'),
                    'new_value' => $family->is_insured
                ]);
                
                $statsService = app(\App\Services\SidebarStatsService::class);
                $clearedCount = $statsService->clearStatsCache(clearAll: true);
                
                Log::info('✅ Sidebar cache cleared after is_insured change', [
                    'family_id' => $family->id,
                    'cleared_count' => $clearedCount
                ]);
            } catch (\Exception $e) {
                Log::error('❌ Error clearing sidebar cache after is_insured change', [
                    'family_id' => $family->id,
                    'error' => $e->getMessage()
                ]);
                // ادامه اجرا حتی اگر کش پاک نشد
            }
        }
    }
}
