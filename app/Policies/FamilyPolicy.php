<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Family;
use App\Enums\InsuranceWizardStep;

class FamilyPolicy
{
    /**
     * بررسی اینکه آیا کاربر می‌تواند خانواده را ویرایش کند
     * 
     * قوانین:
     * - ادمین‌ها: همیشه می‌توانند ویرایش کنند (تغییرات لاگ می‌شود)
     * - خیریه‌ها: فقط خانواده‌های خودشان در مرحله PENDING یا null
     * - بیمه: هیچ دسترسی ویرایشی ندارند
     * 
     * مراحل قابل ویرایش برای خیریه: PENDING, null
     * مراحل غیرقابل ویرایش: REVIEWING, SHARE_ALLOCATION, APPROVED, EXCEL_UPLOAD, INSURED, RENEWAL, REJECTED
     * 
     * مثال‌ها:
     * - خیریه با خانواده PENDING خودش: true
     * - خیریه با خانواده REVIEWING: false
     * - ادمین با هر خانواده‌ای: true (با لاگ)
     *
     * @param User $user
     * @param Family $family
     * @return bool
     */
    public function update(User $user, Family $family): bool
    {
        // ادمین‌ها می‌توانند همه خانواده‌ها را ویرایش کنند
        if ($user->isAdmin()) {
            return true;
        }

        // خیریه‌ها فقط می‌توانند خانواده‌های خودشان را در مرحله PENDING ویرایش کنند
        if ($user->isCharity()) {
            // بررسی مالکیت
            $ownsFamily = $family->charity_id === $user->organization_id;
            
            // بررسی وضعیت wizard - فقط PENDING یا null قابل ویرایش است
            // توجه: wizard_status یک enum object است (از accessor) نه string
            $isEditable = $family->wizard_status === InsuranceWizardStep::PENDING 
                       || $family->wizard_status === null;
            
            return $ownsFamily && $isEditable;
        }

        // کاربران بیمه و سایر نقش‌ها نمی‌توانند خانواده‌ها را ویرایش کنند
        return false;
    }

    /**
     * بررسی اینکه آیا کاربر می‌تواند هر خانواده‌ای را ویرایش کند
     *
     * @param User $user
     * @return bool
     */
    public function updateAny(User $user): bool
    {
        // فقط ادمین‌ها می‌توانند هر خانواده‌ای را ویرایش کنند
        return $user->isAdmin();
    }

    /**
     * بررسی اینکه آیا کاربر می‌تواند اعضای خانواده را ویرایش کند
     * 
     * از همان منطق update استفاده می‌شود چون ویرایش اعضا به معنای ویرایش خانواده است
     *
     * @param User $user
     * @param Family $family
     * @return bool
     */
    public function updateMembers(User $user, Family $family): bool
    {
        return $this->update($user, $family);
    }

    /**
     * Helper method برای بررسی امکان ویرایش در وضعیت فعلی
     * برای استفاده آسان‌تر در Blade views
     * 
     * @param User $user
     * @param Family $family
     * @return bool
     */
    public function canEditInCurrentStatus(User $user, Family $family): bool
    {
        return $this->update($user, $family);
    }

    /**
     * بررسی اینکه آیا کاربر می‌تواند جزئیات خانواده را مشاهده کند
     *
     * @param User $user
     * @param Family $family
     * @return bool
     */
    public function view(User $user, Family $family): bool
    {
        // ادمین و بیمه می‌توانند همه خانواده‌ها را ببینند
        if ($user->isAdmin() || $user->isInsurance()) {
            return true;
        }
        
        // خیریه فقط می‌تواند خانواده‌های خودش را ببیند
        if ($user->isCharity()) {
            return $family->charity_id === $user->organization_id;
        }
        
        return false;
    }
}
