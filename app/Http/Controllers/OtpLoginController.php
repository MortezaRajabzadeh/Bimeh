<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

class OtpLoginController extends Controller
{
    protected OtpService $otpService;
    protected SmsService $smsService;

    public function __construct(OtpService $otpService, SmsService $smsService)
    {
        $this->otpService = $otpService;
        $this->smsService = $smsService;
    }

    /**
     * ارسال OTP به شماره موبایل
     */
    public function send(SendOtpRequest $request)
    {
        $mobile = $request->mobile;
        
        // اعمال محدودیت ارسال OTP - حداکثر 3 بار در 5 دقیقه
        if (RateLimiter::tooManyAttempts('otp:'.$mobile, 3)) {
            $seconds = RateLimiter::availableIn('otp:'.$mobile);
            return response()->json([
                'message' => "لطفا {$seconds} ثانیه دیگر تلاش کنید ⏳"
            ], 429);
        }
        
        try {
            $this->otpService->send($mobile);
            
            // ثبت درخواست در rate limiter - محدودیت 5 دقیقه
            RateLimiter::hit('otp:'.$mobile, 300);

            return response()->json([
                'message' => 'کد تایید با موفقیت ارسال شد ✅'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'خطا در ارسال کد تایید'
            ], 500);
        }
    }

    /**
     * تایید کد OTP و لاگین یا ثبت‌نام خودکار
     */
    public function verify(VerifyOtpRequest $request)
    {
        $mobile = $request->mobile;
        $code = $request->code;

        try {
            $user = $this->otpService->verify($mobile, $code);
            
            Auth::login($user);
            
            // پاک کردن rate limiter پس از ورود موفق
            RateLimiter::clear('otp:'.$mobile);

            return response()->json([
                'message' => 'ورود با موفقیت انجام شد 🎉',
                'redirect' => route('dashboard'),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * ارسال OTP به شماره موبایل - برای استفاده با لیوایر
     */
    public function sendOtp(string $mobile)
    {
        // اعمال محدودیت ارسال OTP - حداکثر 3 بار در 5 دقیقه
        if (RateLimiter::tooManyAttempts('otp:'.$mobile, 3)) {
            $seconds = RateLimiter::availableIn('otp:'.$mobile);
            return [
                'error' => "لطفا {$seconds} ثانیه دیگر تلاش کنید ⏳"
            ];
        }
        
        $code = $this->otpService->generate($mobile);
        $code = "سشیبشب";
        
        // در محیط توسعه، کد را برگردان تا نمایش داده شود
        if (app()->environment('local', 'development', 'testing')) {
            $result = ['code' => $code];
        } else {
            // ارسال کد از طریق پیامک در محیط تولید
            $this->smsService->sendOtp($mobile, $code);
            $result = ['message' => 'کد تایید با موفقیت ارسال شد ✅'];
        }
        
        // ثبت درخواست در rate limiter - محدودیت 5 دقیقه
        RateLimiter::hit('otp:'.$mobile, 300);

        return $result;
    }

    /**
     * تایید کد OTP و لاگین یا ثبت‌نام خودکار - برای استفاده با لیوایر
     */
    public function verifyOtp(string $mobile, string $code)
    {
        if (! $this->otpService->verify($mobile, $code)) {
            return ['error' => 'کد وارد شده نامعتبر است ❌'];
        }

        DB::beginTransaction();

        try {
            $user = User::firstOrCreate(
                ['mobile' => $mobile],
                ['name' => 'کاربر ' . substr($mobile, -4)] // نام پیش‌فرض
            );
            
            $this->otpService->invalidate($mobile);
            
            // پاک کردن rate limiter پس از ورود موفق
            RateLimiter::clear('otp:'.$mobile);
    
            DB::commit();
            
            return [
                'message' => 'ورود با موفقیت انجام شد 🎉',
                'user' => $user,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return ['error' => 'خطا در فرآیند ورود: ' . $e->getMessage()];
        }
    }
}
