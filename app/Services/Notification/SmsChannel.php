<?php
namespace App\Services\Notification;

use Pamenary\LaravelSms\Sms;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function send(string $mobile, string $message): bool
    {
        try {
            (new \Pamenary\LaravelSms\Sms)->sendSMS($mobile, $message);
            return true;
        } catch (\Throwable $e) {
            Log::error("خطا در ارسال پیامک: " . $e->getMessage());
            return false;
        }
    }
}
