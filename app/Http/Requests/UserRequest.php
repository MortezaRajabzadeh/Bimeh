<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($this->user)],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($this->user)],
            'mobile' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($this->user)],
            'organization_id' => ['nullable', 'exists:organizations,id'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'is_active' => ['boolean'],
        ];

        // اگر در حال ایجاد کاربر جدید هستیم، رمز عبور الزامی است
        if ($this->isMethod('post')) {
            $rules['password'] = ['required', 'string', Password::defaults(), 'confirmed'];
        } else {
            // در صورت ویرایش، رمز عبور اختیاری است
            $rules['password'] = ['nullable', 'string', Password::defaults(), 'confirmed'];
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'نام',
            'username' => 'نام کاربری',
            'email' => 'ایمیل',
            'mobile' => 'موبایل',
            'password' => 'رمز عبور',
            'password_confirmation' => 'تکرار رمز عبور',
            'organization_id' => 'سازمان',
            'role' => 'سطح دسترسی',
            'is_active' => 'وضعیت فعال',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'وارد کردن نام الزامی است.',
            'username.required' => 'وارد کردن نام کاربری الزامی است.',
            'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
            'email.email' => 'فرمت ایمیل صحیح نیست.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'mobile.unique' => 'این شماره موبایل قبلاً ثبت شده است.',
            'password.required' => 'وارد کردن رمز عبور الزامی است.',
            'password.confirmed' => 'رمز عبور و تکرار آن مطابقت ندارند.',
            'organization_id.exists' => 'سازمان انتخاب شده معتبر نیست.',
            'role.required' => 'انتخاب سطح دسترسی الزامی است.',
            'role.exists' => 'سطح دسترسی انتخاب شده معتبر نیست.',
        ];
    }
} 
