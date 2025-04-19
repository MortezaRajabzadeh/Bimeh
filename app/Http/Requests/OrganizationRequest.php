<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(['charity', 'insurance'])],
            'code' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ];

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
            'name' => 'نام سازمان',
            'type' => 'نوع سازمان',
            'code' => 'کد سازمان',
            'phone' => 'شماره تماس',
            'email' => 'ایمیل',
            'address' => 'آدرس',
            'description' => 'توضیحات',
            'is_active' => 'وضعیت فعال',
            'logo' => 'لوگو',
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
            'type.in' => 'نوع سازمان باید خیریه یا بیمه باشد.',
            'logo.image' => 'فایل انتخاب شده باید تصویر باشد.',
            'logo.mimes' => 'فرمت فایل لوگو باید یکی از فرمت‌های jpeg, png, jpg یا gif باشد.',
            'logo.max' => 'حجم فایل لوگو نباید بیشتر از 2 مگابایت باشد.',
        ];
    }
} 