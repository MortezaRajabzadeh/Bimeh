<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InsuranceShareRequest extends FormRequest
{
    /**
     * تعیین اینکه آیا کاربر مجاز به این درخواست است
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * قوانین اعتبارسنجی
     */
    public function rules(): array
    {
        return [
            'family_ids' => 'required|array',
            'family_ids.*' => 'exists:families,id',
            'shares' => 'required|array|min:1',
            'shares.*.funding_source_id' => 'required|exists:funding_sources,id',
            'shares.*.percentage' => [
                'required',
                'numeric',
                'min:0.01',
                'max:100',
            ],
            'shares.*.description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * پیام‌های خطای سفارشی
     */
    public function messages(): array
    {
        return [
            'family_ids.required' => 'انتخاب حداقل یک خانواده الزامی است.',
            'family_ids.*.exists' => 'یکی از خانواده‌های انتخابی معتبر نیست.',
            'shares.required' => 'حداقل یک منبع پرداخت وارد کنید.',
            'shares.min' => 'حداقل یک منبع پرداخت وارد کنید.',
            'shares.*.funding_source_id.required' => 'انتخاب منبع مالی الزامی است.',
            'shares.*.funding_source_id.exists' => 'منبع مالی انتخاب شده معتبر نیست.',
            'shares.*.percentage.required' => 'درصد تخصیص الزامی است.',
            'shares.*.percentage.numeric' => 'درصد تخصیص باید عدد باشد.',
            'shares.*.percentage.min' => 'درصد تخصیص باید حداقل 0.01 درصد باشد.',
            'shares.*.percentage.max' => 'درصد تخصیص نمی‌تواند بیش از 100 درصد باشد.',
            'shares.*.description.max' => 'توضیحات نمی‌تواند بیش از 1000 کاراکتر باشد.',
        ];
    }

    /**
     * بررسی اعتبار درخواست قبل از اعمال قوانین
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // بررسی مجموع درصدها
            $total = 0;
            foreach ($this->shares as $share) {
                $total += $share['percentage'];
            }

            if (abs($total - 100) > 0.01) {
                $validator->errors()->add('shares', 'جمع درصدها باید دقیقاً ۱۰۰٪ باشد.');
            }
        });
    }
} 
