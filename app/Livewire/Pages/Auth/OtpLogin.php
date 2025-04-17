<?php

namespace App\Livewire\Pages\Auth;

use Livewire\Component;
use App\Services\OtpService;
use App\Services\SmsService;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class OtpLogin extends Component
{
    #[Validate('required|regex:/^09[0-9]{9}$/')]
    public string $mobile = '';
    public string $otpCode = '';
    public bool $showVerificationForm = false;
    public bool $canResend = false;
    public int $resendTimerCount = 120;
    
    protected OtpService $otpService;
    protected SmsService $smsService;
    
    public function __construct()
    {
        $this->otpService = app(OtpService::class);
        $this->smsService = app(SmsService::class);
    }
    
    public function mount()
    {
        $this->canResend = false;
        $this->resendTimerCount = 120;
    }
    
    // قوانین اعتبارسنجی
    protected function rules()
    {
        return [
            'mobile' => ['required', 'regex:/^09\d{9}$/', 'digits:11'],
            'otpCode' => ['required', 'digits:6']
        ];
    }
    
    // پیام‌های خطا
    protected function messages()
    {
        return [
            'mobile.required' => 'لطفا شماره موبایل خود را وارد کنید',
            'mobile.regex' => 'شماره موبایل باید با ۰۹ شروع شود',
            'mobile.digits' => 'شماره موبایل باید ۱۱ رقم باشد',
            'otpCode.required' => 'لطفا کد تایید را وارد کنید',
            'otpCode.digits' => 'کد تایید باید 6 رقمی باشد',
        ];
    }

    /**
     * ارسال کد OTP به کاربر
     */
    public function sendOtp(): void
    {
        try {
            Log::info('شروع درخواست کد OTP', ['mobile' => $this->mobile]);
            
            $this->validate([
                'mobile' => ['required', 'regex:/^09[0-9]{9}$/']
            ]);

            // تولید کد OTP از طریق سرویس
            $otpCode = $this->otpService->send($this->mobile);

            // در محیط توسعه، کد را نمایش بده
            if (app()->environment('local', 'development')) {
                session()->flash('success', "کد تایید: $otpCode");
                Log::debug('نمایش کد OTP در محیط توسعه', [
                    'mobile' => $this->mobile,
                    'code' => $otpCode
                ]);
            }

            $this->showVerificationForm = true;
            session()->flash('success', 'کد تایید به شماره موبایل شما ارسال شد');
            
            Log::info('کد OTP با موفقیت درخواست شد', [
                'mobile' => $this->mobile
            ]);

        } catch (\Exception $e) {
            Log::error('خطا در درخواست کد تایید:', [
                'mobile' => $this->mobile,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'خطا در ارسال کد تایید. لطفا دوباره تلاش کنید');
        }
    }
    
    /**
     * تایید کد OTP
     */
    public function verifyOtp()
    {
        try {
            $this->validate([
                'otpCode' => ['required', 'digits:6']
            ]);

            $user = $this->otpService->verify($this->mobile, $this->otpCode);
            
            if ($user) {
                Auth::login($user);
                return redirect()->intended(route('home'));
            }

            session()->flash('error', 'کد تایید نامعتبر است');

        } catch (\Exception $e) {
            logger()->error('خطا در تایید کد:', [
                'mobile' => $this->mobile,
                'error' => $e->getMessage()
            ]);
            
            session()->flash('error', 'خطا در تایید کد. لطفا دوباره تلاش کنید');
        }
    }
    
    /**
     * فعال‌سازی دکمه ارسال مجدد کد
     */
    public function enableResend()
    {
        $this->canResend = true;
        $this->resendTimerCount = 0;
    }
    
    /**
     * بازگشت به مرحله ورود شماره موبایل
     */
    public function backToMobile()
    {
        $this->reset(['otpCode', 'showVerificationForm']);
    }
    
    /**
     * نمایش کامپوننت
     */
    public function render()
    {
        return view('livewire.pages.auth.otp-login');
    }
} 