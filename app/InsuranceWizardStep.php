<?php

namespace App;

enum InsuranceWizardStep: string
{
    case PENDING = 'pending';         // در انتظار تایید
    case REVIEWING = 'reviewing';     // در انتظار حمایت
    case SHARE_ALLOCATION = 'share_allocation'; // سهم‌بندی
    case APPROVED = 'approved';       // در انتظار صدور
    case EXCEL_UPLOAD = 'excel_upload';      // آپلود اکسل
    case INSURED = 'insured';         // بیمه شده
    case RENEWAL = 'renewal';         // در انتظار تمدید
    
    /**
     * برچسب فارسی هر مرحله
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'در انتظار تایید',
            self::REVIEWING => 'در انتظار حمایت',
            self::SHARE_ALLOCATION => 'سهم‌بندی',
            self::APPROVED => 'در انتظار صدور',
            self::EXCEL_UPLOAD => 'آپلود اکسل',
            self::INSURED => 'بیمه شده',
            self::RENEWAL => 'در انتظار تمدید',
        };
    }
    
    /**
     * مرحله بعدی در روند wizard
     *
     * @return InsuranceWizardStep|null
     */
    public function nextStep(): ?InsuranceWizardStep
    {
        return match($this) {
            self::PENDING => self::REVIEWING,
            self::REVIEWING => self::SHARE_ALLOCATION,
            self::SHARE_ALLOCATION => self::APPROVED,
            self::APPROVED => self::EXCEL_UPLOAD,
            self::EXCEL_UPLOAD => self::INSURED,
            self::INSURED => self::RENEWAL,
            self::RENEWAL => null,
            default => null
        };
    }
    
    /**
     * مرحله قبلی در روند wizard
     *
     * @return InsuranceWizardStep|null
     */
    public function previousStep(): ?InsuranceWizardStep
    {
        return match($this) {
            self::PENDING => null,
            self::REVIEWING => self::PENDING,
            self::SHARE_ALLOCATION => self::REVIEWING,
            self::APPROVED => self::SHARE_ALLOCATION,
            self::EXCEL_UPLOAD => self::APPROVED,
            self::INSURED => self::EXCEL_UPLOAD,
            self::RENEWAL => self::INSURED,
            default => null
        };
    }
    
    /**
     * بررسی آیا مرحله فعلی نیاز به بررسی شرایط خاصی دارد
     *
     * @return bool
     */
    public function requiresConditionCheck(): bool
    {
        return match($this) {
            self::SHARE_ALLOCATION => true, // نیاز به بررسی وجود سهم‌بندی
            self::EXCEL_UPLOAD => true,     // نیاز به بررسی آپلود فایل اکسل
            default => false
        };
    }
    
    /**
     * دریافت همه مراحل wizard به ترتیب
     *
     * @return array
     */
    public static function orderedSteps(): array
    {
        return [
            self::PENDING,
            self::REVIEWING,
            self::SHARE_ALLOCATION,
            self::APPROVED,
            self::EXCEL_UPLOAD,
            self::INSURED,
            self::RENEWAL,
        ];
    }
    
    /**
     * آیا مرحله فعلی قبل از مرحله داده شده است؟
     * 
     * @param self $step
     * @return bool
     */
    public function isBefore(self $step): bool
    {
        $allSteps = [
            self::PENDING,
            self::REVIEWING,
            self::SHARE_ALLOCATION,
            self::APPROVED, 
            self::EXCEL_UPLOAD,
            self::INSURED,
            self::RENEWAL
        ];
        
        $currentIndex = array_search($this, $allSteps);
        $targetIndex = array_search($step, $allSteps);
        
        return $currentIndex < $targetIndex;
    }
    
    /**
     * آیا مرحله فعلی بعد از مرحله داده شده است؟
     * 
     * @param self $step
     * @return bool
     */
    public function isAfter(self $step): bool
    {
        $allSteps = [
            self::PENDING,
            self::REVIEWING,
            self::SHARE_ALLOCATION,
            self::APPROVED, 
            self::EXCEL_UPLOAD,
            self::INSURED,
            self::RENEWAL
        ];
        
        $currentIndex = array_search($this, $allSteps);
        $targetIndex = array_search($step, $allSteps);
        
        return $currentIndex > $targetIndex;
    }
}
