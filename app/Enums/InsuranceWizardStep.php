<?php

namespace App\Enums;

enum InsuranceWizardStep: string
{
    // مراحل اصلی فرآیند
    case PENDING = 'pending';
    case REVIEWING = 'reviewing';
    case SHARE_ALLOCATION = 'share_allocation'; // تخصیص سهم
    // در حال بررسی (آماده برای تخصیص سهم)
    case APPROVED = 'approved';             // تایید شده (آماده برای دانلود اکسل)
    case EXCEL_UPLOAD = 'excel_upload'; // در انتظار آپلود اکسل
    case INSURED = 'insured';           // بیمه شده نهایی

    // مراحل دیگر
    case RENEWAL = 'renewal';           // در حال تمدید
    case REJECTED = 'rejected';         // رد شده

    /**
     * یک برچسب فارسی خوانا برای هر مرحله برمی‌گرداند.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'در انتظار بررسی',
            self::REVIEWING => 'در حال بررسی',
            self::APPROVED => 'تایید شده',
            self::SHARE_ALLOCATION => 'تخصیص سهم',
            self::EXCEL_UPLOAD => 'در انتظار صدور',
            self::INSURED => 'بیمه شده',
            self::RENEWAL => 'در حال تمدید',
            self::REJECTED => 'رد شده',
        };
    }

    /**
     * مرحله بعدی در فرآیند را برمی‌گرداند.
     */
// in app/Enums/InsuranceWizardStep.php

public function nextStep(): ?self
{
    return match ($this) {
        self::PENDING => self::REVIEWING,
        self::REVIEWING => self::SHARE_ALLOCATION,
        self::SHARE_ALLOCATION => self::APPROVED, // <-- اصلاح شد
        self::APPROVED => self::EXCEL_UPLOAD,     // <-- مرحله بعد از تأیید
        self::EXCEL_UPLOAD => self::INSURED,
        default => null,
    };
}

// همچنین legacyStatus برای share_allocation باید 'reviewing' یا 'approved' باشد
// چون خانواده هنوز در مرحله تأیید است
public function legacyStatus(): string
{
    return match ($this) {
        self::PENDING => 'pending',
        self::REVIEWING => 'reviewing',
        self::SHARE_ALLOCATION => 'reviewing', // یا approved - بستگی به نمایش در تب‌ها دارد
        self::APPROVED => 'approved',
        self::EXCEL_UPLOAD => 'approved',      // در تب 'approved' نمایش داده می‌شود
        self::INSURED => 'insured',
        self::RENEWAL => 'renewal',
        self::REJECTED => 'rejected',
    };
}
}
