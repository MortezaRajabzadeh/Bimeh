<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MemberRequest extends FormRequest
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
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'national_code' => [
                'required',
                'string',
                'size:10',
                Rule::unique('members')->ignore($this->member)
            ],
            'father_name' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'marital_status' => ['nullable', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'education' => ['nullable', Rule::in(['illiterate', 'primary', 'secondary', 'diploma', 'associate', 'bachelor', 'master', 'doctorate'])],
            'relationship' => ['required', Rule::in(['head', 'spouse', 'child', 'parent', 'other'])],
            'is_head' => ['boolean'],
            'has_disability' => ['boolean'],
            'has_chronic_disease' => ['boolean'],
            'has_insurance' => ['boolean'],
            'insurance_type' => ['nullable', 'string', 'max:255'],
            'special_conditions' => ['nullable', 'string'],
            'occupation' => ['nullable', 'string', 'max:255'],
            'is_employed' => ['boolean'],
            'mobile' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:20'],
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
            'first_name' => 'نام',
            'last_name' => 'نام خانوادگی',
            'national_code' => 'کد ملی',
            'father_name' => 'نام پدر',
            'birth_date' => 'تاریخ تولد',
            'gender' => 'جنسیت',
            'marital_status' => 'وضعیت تأهل',
            'education' => 'تحصیلات',
            'relationship' => 'نسبت',
            'is_head' => 'سرپرست خانوار',
            'has_disability' => 'معلولیت',
            'has_chronic_disease' => 'بیماری مزمن',
            'has_insurance' => 'دارای بیمه',
            'insurance_type' => 'نوع بیمه',
            'special_conditions' => 'شرایط خاص',
            'occupation' => 'شغل',
            'is_employed' => 'شاغل',
            'mobile' => 'موبایل',
            'phone' => 'تلفن',
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
            'first_name.required' => 'وارد کردن نام الزامی است.',
            'last_name.required' => 'وارد کردن نام خانوادگی الزامی است.',
            'national_code.required' => 'وارد کردن کد ملی الزامی است.',
            'national_code.size' => 'کد ملی باید ۱۰ رقم باشد.',
            'national_code.unique' => 'این کد ملی قبلاً ثبت شده است.',
            'birth_date.date' => 'فرمت تاریخ تولد صحیح نیست.',
            'gender.in' => 'جنسیت باید مرد یا زن باشد.',
            'marital_status.in' => 'وضعیت تأهل باید یکی از موارد مجرد، متأهل، مطلقه یا بیوه باشد.',
            'education.in' => 'تحصیلات وارد شده معتبر نیست.',
            'relationship.required' => 'انتخاب نسبت الزامی است.',
            'relationship.in' => 'نسبت باید یکی از موارد سرپرست، همسر، فرزند، والدین یا سایر باشد.',
        ];
    }
}
