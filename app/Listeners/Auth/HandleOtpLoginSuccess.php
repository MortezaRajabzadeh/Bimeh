<?php

namespace App\Listeners\Auth;

use App\Events\Auth\OtpLoginSuccessful;
use Illuminate\Support\Facades\Log;

class HandleOtpLoginSuccess
{
    /**
     * Handle the event.
     */
    public function handle(OtpLoginSuccessful $event): void
    {
        // لاگ کردن ورود موفق
        Log::info('OTP login successful', [
            'user_id' => $event->user->id,
            'mobile' => $event->mobile,
            'is_new_user' => $event->isNewUser
        ]);

        // می‌توانید اینجا اقدامات دیگری انجام دهید
        // مثلاً: ارسال نوتیفیکیشن خوش‌آمدگویی به کاربر جدید
        if ($event->isNewUser) {
            // $event->user->notify(new WelcomeNotification);
        }

        // بروزرسانی آخرین زمان ورود
        $event->user->update([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip()
        ]);
    }
} 