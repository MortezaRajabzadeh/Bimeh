<?php

namespace App\Repositories;

use App\Models\OtpCode;
use Illuminate\Support\Carbon;

class OtpRepository
{
    /**
     * ذخیره یا به‌روزرسانی کد OTP
     */
    public function storeOrUpdate(string $mobile, string $code, int $expiryMinutes = 5)
    {
        return OtpCode::updateOrCreate(
            ['mobile' => $mobile],
            [
                'code' => $code,
                'expires_at' => now()->addMinutes($expiryMinutes),
            ]
        );
    }
    
    /**
     * بررسی و یافتن کد OTP معتبر
     */
    public function findValidCode(string $mobile, string $code)
    {
        return OtpCode::where('mobile', $mobile)
            ->where('code', $code)
            ->where('expires_at', '>=', now())
            ->first();
    }
    
    /**
     * حذف کد OTP با شماره موبایل
     */
    public function deleteByMobile(string $mobile): void
    {
        OtpCode::where('mobile', $mobile)->delete();
    }

    /**
     * حذف کدهای منقضی شده
     */
    public function deleteExpired(): void
    {
        OtpCode::where('expires_at', '<', Carbon::now())->delete();
    }
} 