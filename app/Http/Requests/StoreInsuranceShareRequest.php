<?php

namespace App\Http\Requests;

use App\Models\InsuranceShare;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInsuranceShareRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // یا بررسی مجوز مناسب
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'family_insurance_id' => 'required|exists:family_insurances,id',
            'percentage' => [
                'required',
                'numeric',
                'min:0.01',
                'max:100',
                function ($attribute, $value, $fail) {
                    $familyInsuranceId = $this->input('family_insurance_id');
                    $currentTotal = InsuranceShare::where('family_insurance_id', $familyInsuranceId)->sum('percentage');
                    
                    if ($currentTotal + $value > 100) {
                        $remaining = 100 - $currentTotal;
                        $fail("مجموع درصدهای سهم‌بندی نمی‌تواند از ۱۰۰٪ بیشتر باشد. درصد باقیمانده: {$remaining}٪");
                    }
                },
            ],
            'payer_type' => 'required|in:insurance_company,charity,bank,government,individual_donor,csr_budget,other',
            'payer_name' => 'required|string|max:255',
            'payer_organization_id' => [
                'nullable',
                'exists:organizations,id',
                Rule::requiredIf(function () {
                    return in_array($this->input('payer_type'), ['insurance_company', 'charity', 'bank']);
                }),
            ],
            'payer_user_id' => [
                'nullable',
                'exists:users,id',
                Rule::requiredIf(function () {
                    return $this->input('payer_type') === 'individual_donor';
                }),
            ],
            'description' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'family_insurance_id.required' => 'انتخاب بیمه خانواده الزامی است.',
            'family_insurance_id.exists' => 'بیمه خانواده انتخاب شده معتبر نیست.',
            'percentage.required' => 'درصد مشارکت الزامی است.',
            'percentage.numeric' => 'درصد مشارکت باید عدد باشد.',
            'percentage.min' => 'درصد مشارکت باید حداقل ۰.۰۱ باشد.',
            'percentage.max' => 'درصد مشارکت نمی‌تواند بیش از ۱۰۰ باشد.',
            'payer_type.required' => 'نوع پرداخت‌کننده الزامی است.',
            'payer_type.in' => 'نوع پرداخت‌کننده انتخاب شده معتبر نیست.',
            'payer_name.required' => 'نام پرداخت‌کننده الزامی است.',
            'payer_name.max' => 'نام پرداخت‌کننده نمی‌تواند بیش از ۲۵۵ کاراکتر باشد.',
            'payer_organization_id.exists' => 'سازمان انتخاب شده معتبر نیست.',
            'payer_organization_id.required' => 'انتخاب سازمان برای این نوع پرداخت‌کننده الزامی است.',
            'payer_user_id.exists' => 'کاربر انتخاب شده معتبر نیست.',
            'payer_user_id.required' => 'انتخاب کاربر برای فرد خیر الزامی است.',
            'description.max' => 'توضیحات نمی‌تواند بیش از ۱۰۰۰ کاراکتر باشد.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'family_insurance_id' => 'بیمه خانواده',
            'percentage' => 'درصد مشارکت',
            'payer_type' => 'نوع پرداخت‌کننده',
            'payer_name' => 'نام پرداخت‌کننده',
            'payer_organization_id' => 'سازمان',
            'payer_user_id' => 'کاربر',
            'description' => 'توضیحات',
        ];
    }
}
