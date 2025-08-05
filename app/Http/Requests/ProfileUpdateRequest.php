<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * تعیین اینکه آیا کاربر مجاز به انجام این درخواست است
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قوانین اعتبارسنجی برای درخواست
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'mobile' => ['required', 'string', 'regex:/^09[0-9]{9}$/', Rule::unique(User::class)->ignore($this->user()->id)],
            'organization_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,svg', 'max:2048'],
        ];
    }

    /**
     * پیام‌های خطای سفارشی
     */
    public function messages(): array
    {
        return [
            'name.required' => 'نام کاربری الزامی است',
            'name.string' => 'نام کاربری باید متن باشد',
            'name.max' => 'نام کاربری نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'email.required' => 'ایمیل الزامی است',
            'email.string' => 'ایمیل باید متن باشد',
            'email.email' => 'فرمت ایمیل نامعتبر است',
            'email.max' => 'ایمیل نمی‌تواند بیشتر از ۲۵۵ کاراکتر باشد',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است',
            'mobile.required' => 'شماره موبایل الزامی است',
            'mobile.string' => 'شماره موبایل باید متن باشد',
            'mobile.regex' => 'فرمت شماره موبایل نامعتبر است',
            'mobile.unique' => 'این شماره موبایل قبلاً ثبت شده است',
            'organization_logo.image' => 'فایل انتخاب شده باید تصویر باشد',
            'organization_logo.mimes' => 'فرمت‌های مجاز: JPG، PNG، SVG',
            'organization_logo.max' => 'حجم فایل نمی‌تواند بیشتر از 2 مگابایت باشد',
        ];
    }
} 
