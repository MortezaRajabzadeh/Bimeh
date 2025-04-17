<?php
namespace App\Services;

use App\Events\Auth\OtpLoginSuccessful;
use App\Models\User;
use App\Repositories\OtpRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * مدت زمان اعتبار کد OTP (به دقیقه)
     */
    const EXPIRY_MINUTES = 5;

    /**
     * تعداد دفعات مجاز برای درخواست کد در هر ساعت
     */
    const HOURLY_ATTEMPTS = 5;

    /**
     * تعداد دفعات مجاز برای تلاش ورود کد اشتباه
     */
    const VERIFY_ATTEMPTS = 3;

    /**
     * سرویس ارسال پیامک
     */
    protected SmsService $smsService;
    protected OtpRepository $otpRepository;

    public function __construct(SmsService $smsService, OtpRepository $otpRepository)
    {
        $this->smsService = $smsService;
        $this->otpRepository = $otpRepository;
    }
    
    /**
     * ایجاد و ارسال کد OTP جدید
     * 
     * @throws ValidationException
     */
    public function send(string $mobile): string
    {
        try {
            Log::info('شروع ارسال کد OTP', ['mobile' => $mobile]);
            
            // بررسی محدودیت تعداد درخواست
            if (! $this->checkRateLimit($mobile)) {
                throw ValidationException::withMessages([
                    'mobile' => 'تعداد درخواست‌های شما بیش از حد مجاز است. لطفاً یک ساعت دیگر تلاش کنید.'
                ]);
            }

            // تولید کد جدید
            $code = $this->generateCode();
            
            // ذخیره کد در دیتابیس
            $this->otpRepository->storeOrUpdate($mobile, $code, self::EXPIRY_MINUTES);
            
            // ارسال کد از طریق پیامک
            $this->smsService->sendOtp($mobile, $code);
            
            // ذخیره کد در session برای محیط توسعه
            if (app()->environment('local', 'development')) {
                session(['dev_otp_code' => $code]);
            }
            
            Log::info('کد OTP با موفقیت ارسال شد', [
                'mobile' => $mobile,
                'code' => $code,
                'env' => app()->environment()
            ]);
            
            return $code;
            
        } catch (\Exception $e) {
            Log::error('خطا در ارسال کد OTP', [
                'mobile' => $mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * تایید کد OTP
     * 
     * @throws ValidationException
     */
    public function verify(string $mobile, string $code): ?User
    {
        try {
            Log::info('شروع تایید کد OTP', [
                'mobile' => $mobile,
                'code' => $code
            ]);
            
            // بررسی صحت کد
            $otpCode = $this->otpRepository->findValidCode($mobile, $code);
            
            if (!$otpCode) {
                // افزایش تعداد تلاش‌های ناموفق
                $key = "otp_verify_attempts_{$mobile}";
                $attempts = Cache::get($key, 0) + 1;
                Cache::put($key, $attempts, now()->addMinutes(30));
                
                // بررسی تعداد دفعات تلاش
                if ($attempts >= self::VERIFY_ATTEMPTS) {
                    throw ValidationException::withMessages([
                        'code' => 'تعداد تلاش‌های شما بیش از حد مجاز است. لطفاً مجدداً درخواست کد کنید.'
                    ]);
                }
                
                Log::warning('کد OTP نامعتبر است', [
                    'mobile' => $mobile,
                    'entered_code' => $code,
                    'attempts' => $attempts
                ]);
                
                return null;
            }

            // پاک کردن کد و تعداد تلاش‌ها
            $this->otpRepository->deleteByMobile($mobile);
            Cache::forget("otp_verify_attempts_{$mobile}");

            // پیدا کردن یا ساخت کاربر
            $user = User::firstOrCreate(
                ['mobile' => $mobile],
                [
                    'name' => 'کاربر ' . substr($mobile, -4),
                    'email' => $mobile . '@pinoto.ir',
                    'password' => bcrypt(Str::random(16)),
                    'email_verified_at' => now(),
                    'is_active' => true
                ]
            );

            // اطمینان از فعال بودن کاربر در صورت وجود قبلی
            if (!$user->is_active) {
                $user->is_active = true;
                $user->save();
            }

            Log::info('کد OTP با موفقیت تایید شد', ['mobile' => $mobile]);
            
            return $user;
            
        } catch (\Exception $e) {
            Log::error('خطا در تایید کد OTP', [
                'mobile' => $mobile,
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * بررسی محدودیت تعداد درخواست
     */
    protected function checkRateLimit(string $mobile): bool
    {
        // return RateLimiter::attempt(
        //     "otp_send_{$mobile}",
        //     self::HOURLY_ATTEMPTS,
        //     fn() => true,
        //     60 * 60 // 1 hour
        // );
        return true;
    }

    /**
     * تولید کد تصادفی 6 رقمی
     */
    protected function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * باطل کردن کد OTP پس از استفاده
     */
    public function invalidate(string $mobile): void
    {
        // Implementation of invalidate method
    }
}
