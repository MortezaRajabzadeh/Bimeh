<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FamilyStatusRequest extends FormRequest
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
        return [
            'status' => ['required', Rule::in(['pending', 'reviewing', 'approved', 'rejected'])],
            'rejection_reason' => ['required_if:status,rejected', 'nullable', 'string'],
            'insurance_id' => ['required_if:status,approved', 'nullable', 'exists:organizations,id'],
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
            'status' => 'وضعیت',
            'rejection_reason' => 'دلیل رد درخواست',
            'insurance_id' => 'سازمان بیمه',
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
            'status.required' => 'انتخاب وضعیت الزامی است.',
            'status.in' => 'وضعیت باید یکی از موارد در انتظار، در حال بررسی، تأیید شده یا رد شده باشد.',
            'rejection_reason.required_if' => 'در صورت رد درخواست، وارد کردن دلیل رد الزامی است.',
            'insurance_id.required_if' => 'در صورت تأیید درخواست، انتخاب سازمان بیمه الزامی است.',
            'insurance_id.exists' => 'سازمان بیمه انتخاب شده معتبر نیست.',
        ];
    }
} 
