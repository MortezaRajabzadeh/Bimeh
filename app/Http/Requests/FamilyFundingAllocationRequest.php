<?php

namespace App\Http\Requests;

use App\Models\FamilyFundingAllocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FamilyFundingAllocationRequest extends FormRequest
{
    /**
     * تعیین اینکه آیا کاربر مجاز به این درخواست است
     */
    public function authorize(): bool
    {
        // در حال حاضر همه کاربران مجاز هستند
        // می‌توانید براساس نقش کاربر یا سایر شرایط این را تغییر دهید
        return true;
    }

    /**
     * قوانین اعتبارسنجی
     */
    public function rules(): array
    {
        $allocationId = $this->route('allocation') ? $this->route('allocation')->id : null;
        $isBulkAllocation = !$this->has('family_id');
        
        $rules = [
            'funding_source_id' => [
                'required',
                'integer',
                'exists:funding_sources,id'
            ],
            'percentage' => [
                'required',
                'numeric',
                'min:0.01',
                'max:100',
                'regex:/^\d{1,3}(\.\d{1,2})?$/', // حداکثر 2 رقم اعشار
            ],
            'amount' => [
                'nullable',
                'numeric',
                'min:0'
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000'
            ]
        ];
        
        // اگر تخصیص برای یک خانواده خاص است
        if (!$isBulkAllocation) {
            $rules['family_id'] = [
                'required',
                'integer',
                'exists:families,id'
            ];
            
            // اضافه کردن اعتبارسنجی مجموع درصدها فقط برای تخصیص به یک خانواده
            $rules['percentage'][] = function ($attribute, $value, $fail) use ($allocationId) {
                $this->validateTotalPercentage($value, $fail, $allocationId);
            };
        }
        
        return $rules;
    }

    /**
     * پیام‌های خطای سفارشی
     */
    public function messages(): array
    {
        return [
            'family_id.required' => 'انتخاب خانواده الزامی است.',
            'family_id.exists' => 'خانواده انتخابی معتبر نیست.',
            
            'funding_source_id.required' => 'انتخاب منبع مالی الزامی است.',
            'funding_source_id.exists' => 'منبع مالی انتخابی معتبر نیست.',
            
            'percentage.required' => 'درصد تخصیص الزامی است.',
            'percentage.numeric' => 'درصد تخصیص باید عدد باشد.',
            'percentage.min' => 'درصد تخصیص باید حداقل 0.01 درصد باشد.',
            'percentage.max' => 'درصد تخصیص نمی‌تواند بیش از 100 درصد باشد.',
            'percentage.regex' => 'درصد تخصیص باید حداکثر 2 رقم اعشار داشته باشد.',
            
            'amount.numeric' => 'مبلغ باید عدد باشد.',
            'amount.min' => 'مبلغ نمی‌تواند منفی باشد.',
            
            'description.max' => 'توضیحات نمی‌تواند بیش از 1000 کاراکتر باشد.'
        ];
    }

    /**
     * نام‌های فیلدها برای نمایش در پیام‌های خطا
     */
    public function attributes(): array
    {
        return [
            'family_id' => 'خانواده',
            'funding_source_id' => 'منبع مالی',
            'percentage' => 'درصد تخصیص',
            'amount' => 'مبلغ',
            'description' => 'توضیحات'
        ];
    }

    /**
     * اعتبارسنجی مجموع درصدها
     */
    private function validateTotalPercentage($value, $fail, $allocationId = null)
    {
        $familyId = $this->input('family_id');
        
        if (!$familyId) {
            return; // اگر family_id نامعتبر است، قانون دیگری آن را بررسی می‌کند
        }

        // محاسبه مجموع درصدهای موجود (به جز تخصیص جاری در صورت ویرایش)
        $query = FamilyFundingAllocation::where('family_id', $familyId);
        
        if ($allocationId) {
            $query->where('id', '!=', $allocationId);
        }
        
        $currentTotal = $query->sum('percentage');
        
        if (($currentTotal + $value) > 100) {
            $remaining = 100 - $currentTotal;
            $fail("مجموع درصد تخصیص‌ها نمی‌تواند از 100% بیشتر باشد. درصد باقی‌مانده: {$remaining}%");
        }
    }

    /**
     * تنظیم داده‌ها پس از اعتبارسنجی
     */
    public function passedValidation()
    {
        // اگر مبلغ مشخص نشده، آن را خالی نگه دارید تا در سرویس محاسبه شود
        if (empty($this->amount)) {
            $this->merge(['amount' => null]);
        }
    }

    /**
     * قوانین اضافی برای سناریوهای خاص
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // بررسی اینکه منبع مالی فعال باشد
            if ($this->funding_source_id) {
                $fundingSource = \App\Models\FundingSource::find($this->funding_source_id);
                if ($fundingSource && !$fundingSource->is_active) {
                    $validator->errors()->add('funding_source_id', 'منبع مالی انتخابی فعال نیست.');
                }
            }

            // بررسی اینکه خانواده تایید شده باشد (فقط در حالت تخصیص تکی)
            if ($this->has('family_id') && $this->family_id) {
                $family = \App\Models\Family::find($this->family_id);
                if ($family && $family->verification_status !== 'approved') {
                    $validator->errors()->add('family_id', 'خانواده انتخابی هنوز تایید نشده است.');
                }
            }
        });
    }
} 
