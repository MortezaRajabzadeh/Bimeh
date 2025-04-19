<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FamilyRequest extends FormRequest
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
            'region_id' => ['required', 'exists:regions,id'],
            'address' => ['required', 'string'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'housing_status' => ['nullable', Rule::in(['owner', 'tenant', 'relative', 'other'])],
            'housing_description' => ['nullable', 'string'],
            'additional_info' => ['nullable', 'string'],
        ];

        // قوانین اضافی برای ادمین‌ها و کاربران بیمه
        if ($this->user() && ($this->user()->isAdmin() || $this->user()->isInsurance())) {
            $rules['status'] = ['sometimes', Rule::in(['pending', 'reviewing', 'approved', 'rejected'])];
            $rules['rejection_reason'] = ['required_if:status,rejected', 'nullable', 'string'];
            $rules['insurance_id'] = ['required_if:status,approved', 'nullable', 'exists:organizations,id'];
            $rules['poverty_confirmed'] = ['boolean'];
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
            'region_id' => 'منطقه',
            'address' => 'آدرس',
            'postal_code' => 'کد پستی',
            'housing_status' => 'وضعیت مسکن',
            'housing_description' => 'توضیحات مسکن',
            'additional_info' => 'اطلاعات تکمیلی',
            'status' => 'وضعیت',
            'rejection_reason' => 'دلیل رد درخواست',
            'insurance_id' => 'سازمان بیمه',
            'poverty_confirmed' => 'تأیید شرایط کم‌برخورداری',
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
            'region_id.required' => 'انتخاب منطقه الزامی است.',
            'region_id.exists' => 'منطقه انتخاب شده معتبر نیست.',
            'address.required' => 'وارد کردن آدرس الزامی است.',
            'housing_status.in' => 'وضعیت مسکن باید یکی از موارد مالک، مستأجر، اقوام یا سایر باشد.',
            'status.in' => 'وضعیت باید یکی از موارد در انتظار، در حال بررسی، تأیید شده یا رد شده باشد.',
            'rejection_reason.required_if' => 'در صورت رد درخواست، وارد کردن دلیل رد الزامی است.',
            'insurance_id.required_if' => 'در صورت تأیید درخواست، انتخاب سازمان بیمه الزامی است.',
            'insurance_id.exists' => 'سازمان بیمه انتخاب شده معتبر نیست.',
        ];
    }
} 