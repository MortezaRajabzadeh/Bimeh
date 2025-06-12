<?php

namespace App\Http\Livewire\Auth;

use Livewire\Component;

class UserTypeSelection extends Component
{
    /**
     * رندر کامپوننت 
     */
    public function render()
    {
        return view('livewire.auth.user-type-selection');
    }
    
    /**
     * هدایت به صفحه لاگین خیریه
     */
    public function loginAsCharity()
    {
        return redirect()->route('charity.login');
    }
    
    /**
     * هدایت به صفحه لاگین شرکت بیمه
     */
    public function loginAsInsurance()
    {
        return redirect()->route('insurance.login');
    }
    
    /**
     * هدایت به صفحه لاگین مدیر سیستم
     */
    public function loginAsAdmin()
    {
        return redirect()->route('admin.login');
    }
    
    /**
     * مشاهده لاگ تغییرات
     */
    public function viewChangeLogs()
    {
        return redirect()->route('logs.index');
    }
} 
