<?php

namespace App\Enums;

enum InsuranceWizardStep: string
{
    // مراحل اصلی فرآیند
    case PENDING = 'pending';
    case REVIEWING = 'reviewing';       // در حال بررسی (آماده برای تخصیص سهم)
    case APPROVED = 'approved';         // تایید شده (آماده برای دانلود اکسل)
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
            self::EXCEL_UPLOAD => 'در انتظار صدور',
            self::INSURED => 'بیمه شده',
            self::RENEWAL => 'در حال تمدید',
            self::REJECTED => 'رد شده',
        };
    }

    /**
     * مرحله بعدی در فرآیند را برمی‌گرداند.
     */
    public function nextStep(): ?self
    {
        return match ($this) {
            self::PENDING => self::REVIEWING,
            self::REVIEWING => self::APPROVED,
            self::APPROVED => self::EXCEL_UPLOAD,
            self::EXCEL_UPLOAD => self::INSURED,
            default => null, // برای مراحل دیگر، مرحله بعدی تعریف نشده
        };
    }

    /**
     * مقدار وضعیت قدیمی (در ستون `status` جدول `families`) را برمی‌گرداند
     * این برای حفظ سازگاری با کدهای قدیمی شماست.
     */
    public function legacyStatus(): string
    {
        return match ($this) {
            self::PENDING => 'pending',
            self::REVIEWING => 'reviewing',
            self::APPROVED => 'approved',
            self::EXCEL_UPLOAD => 'excel', // یا 'approved' بر اساس نیاز شما
            self::INSURED => 'insured',
            self::RENEWAL => 'renewal',
            self::REJECTED => 'rejected',
        };
    }
}