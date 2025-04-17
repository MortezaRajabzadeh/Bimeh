<?php

namespace App\Livewire\Auth;

use App\Services\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class OtpLogin extends Component
{
    public string $mobile = '';
    public string $otpCode = '';
    public bool $showVerificationForm = false;
    public ?int $resendTimerCount = null;
    public bool $canResend = false;

    /**
     * قوانین اعتبارسنجی
     */
    protected function rules(): array
    {
        return [
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
            'otpCode' => ['required', 'digits:6']
        ];
    }

    /**
     * پیام‌های خطای اعتبارسنجی
     */
    protected function messages(): array
    {
        return [
            'mobile.required' => 'لطفاً شماره موبایل خود را وارد کنید.',
            'mobile.regex' => 'شماره موبایل وارد شده معتبر نیست.',
            'otpCode.required' => 'لطفاً کد تایید را وارد کنید.',
            'otpCode.digits' => 'کد تایید باید ۶ رقم باشد.'
        ];
    }

    /**
     * ارسال کد تایید
     */
    public function sendOtp(OtpService $otpService): void
    {
        try {
            $this->validate(['mobile' => $this->rules()['mobile']]);
            
            $otpService->send($this->mobile);
            
            $this->showVerificationForm = true;
            $this->resendTimerCount = 120; // 2 minutes
            $this->canResend = false;
            
            $this->dispatch('otp-sent', mobile: $this->mobile);
            
        } catch (ValidationException $e) {
            $this->addError('mobile', $e->getMessage());
        } catch (\Exception $e) {
            $this->addError('mobile', 'خطا در ارسال کد تایید. لطفاً دوباره تلاش کنید.');
            logger()->error('OTP Send Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * تایید کد و ورود
     */
    public function verifyOtp(OtpService $otpService): void
    {
        try {
            $this->validate();
            
            $user = $otpService->verify($this->mobile, $this->otpCode);
            
            Auth::login($user);
            
            $this->dispatch('otp-verified');
            
            redirect()->intended(route('dashboard'));
            
        } catch (ValidationException $e) {
            $this->addError('otpCode', $e->getMessage());
            $this->otpCode = '';
        } catch (\Exception $e) {
            $this->addError('otpCode', 'خطا در تایید کد. لطفاً دوباره تلاش کنید.');
            logger()->error('OTP Verify Error', ['error' => $e->getMessage()]);
        }
    }

    /**
     * بازگشت به فرم شماره موبایل
     */
    public function backToMobile(): void
    {
        $this->reset(['showVerificationForm', 'otpCode', 'resendTimerCount', 'canResend']);
    }

    /**
     * فعال کردن دکمه ارسال مجدد
     */
    public function enableResend(): void
    {
        $this->canResend = true;
        $this->resendTimerCount = null;
    }

    /**
     * رندر کامپوننت
     */
    public function render()
    {
        return view('livewire.pages.auth.otp-login')
            ->layout('layouts.auth');
    }
} 