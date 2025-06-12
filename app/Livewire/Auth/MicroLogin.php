<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class MicroLogin extends Component
{
    public string $identifier = ''; // نام کاربری یا ایمیل
    public string $password = ''; // رمز عبور
    
    /**
     * قوانین اعتبارسنجی
     */
    protected function rules()
    {
        return [
            'identifier' => ['required', 'string', 'min:3'],
            'password' => ['required'],
        ];
    }
    
    /**
     * پیام‌های خطای اعتبارسنجی
     */
    protected function messages()
    {
        return [
            'identifier.required' => 'لطفاً نام کاربری یا ایمیل خود را وارد کنید.',
            'identifier.min' => 'نام کاربری باید حداقل ۳ حرف باشد.',
            'password.required' => 'لطفاً رمز عبور خود را وارد کنید.',
        ];
    }
    
    /**
     * ورود به سیستم
     */
    public function login()
    {
        $this->validate();
        
        try {
            // تلاش برای ورود با نام کاربری
            if (Auth::attempt(['username' => $this->identifier, 'password' => $this->password, 'is_active' => true])) {
                session()->regenerate();
                return $this->redirectBasedOnUserType();
            }
            
            // تلاش برای ورود با ایمیل
            if (Auth::attempt(['email' => $this->identifier, 'password' => $this->password, 'is_active' => true])) {
                session()->regenerate();
                return $this->redirectBasedOnUserType();
            }
            
            // تلاش برای ورود با موبایل
            if (Auth::attempt(['mobile' => $this->identifier, 'password' => $this->password, 'is_active' => true])) {
                session()->regenerate();
                return $this->redirectBasedOnUserType();
            }
            
            // در صورت عدم موفقیت در ورود
            $this->addError('password', 'اطلاعات ورود صحیح نیست یا حساب کاربری غیرفعال است.');
            
        } catch (ValidationException $e) {
            $this->addError('password', $e->getMessage());
        } catch (\Exception $e) {
            $this->addError('password', 'خطا در ورود به سیستم. لطفاً دوباره تلاش کنید.');
        }
    }
    
    /**
     * هدایت کاربر براساس نوع کاربری
     */
    private function redirectBasedOnUserType()
    {
        $user = Auth::user();
        
        if (!$user || !$user->is_active) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            $this->addError('password', 'حساب کاربری شما غیرفعال است.');
            return;
        }
        
        if ($user->user_type === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif ($user->user_type === 'charity') {
            return redirect()->route('charity.dashboard');
        } elseif ($user->user_type === 'insurance') {
            return redirect()->route('insurance.dashboard');
        }
        
        return redirect()->route('dashboard');
    }
    
    /**
     * رندر کامپوننت
     */
    public function render()
    {
        return view('livewire.auth.micro-login');
    }
} 
