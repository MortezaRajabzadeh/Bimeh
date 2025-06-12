<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * ارسال پیامک
     */
    public function send(string $mobile, string $message): bool
    {
        try {
                'mobile' => $mobile,
                'message' => $message
            ]);

            // در محیط توسعه فقط لاگ می‌کنیم
            if (app()->environment('local', 'development')) {
                return true;
            }

            // TODO: پیاده‌سازی ارسال واقعی پیامک
            // مثلاً با کاوه‌نگار یا ملی پیامک
            return true;

        } catch (\Exception $e) {
                'mobile' => $mobile,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * ارسال کد OTP از طریق پیامک
     */
    public function sendOtp(string $mobile, string $code): bool
    {
        $message = "کد تایید شما: $code";
        return $this->send($mobile, $message);
    }
}
