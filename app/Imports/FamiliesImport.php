<?php

namespace App\Imports;

use App\Models\Family;
use App\Models\Member;
use App\Models\User;
use App\Services\FamilyService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FamiliesImport implements ToCollection, WithHeadingRow
{
    protected User $user;
    protected int $regionId;
    protected array $results = [
        'success' => 0,
        'failed' => 0,
        'errors' => [],
    ];

    /**
     * ایجاد نمونه کلاس
     */
    public function __construct(User $user, int $regionId)
    {
        $this->user = $user;
        $this->regionId = $regionId;
    }

    /**
     * پردازش رکوردهای اکسل
     */
    public function collection(Collection $rows)
    {
        $familyService = app(FamilyService::class);
        
        foreach ($rows as $index => $row) {
            DB::beginTransaction();
            
            try {
                // اعتبارسنجی داده‌های خانواده
                $familyData = $this->extractFamilyData($row);
                $validatedFamily = $this->validateFamilyData($familyData, $index + 2);
                
                if (!$validatedFamily) {
                    DB::rollBack();
                    continue;
                }
                
                // ایجاد خانواده
                $family = $familyService->registerFamily([
                    'region_id' => $this->regionId,
                    'address' => $familyData['address'],
                    'postal_code' => $familyData['postal_code'] ?? null,
                    'housing_status' => $familyData['housing_status'] ?? null,
                    'housing_description' => $familyData['housing_description'] ?? null,
                    'additional_info' => $familyData['additional_info'] ?? null,
                ], $this->user);
                
                // استخراج اطلاعات سرپرست خانوار
                $headData = $this->extractMemberData($row, 'head_');
                $validatedHead = $this->validateMemberData($headData, $index + 2, true);
                
                if (!$validatedHead) {
                    DB::rollBack();
                    continue;
                }
                
                // ایجاد سرپرست خانوار
                $headData['is_head'] = true;
                $headData['relationship'] = 'head';
                $familyService->addMember($family, $headData);
                
                // استخراج اطلاعات سایر اعضا (اگر وجود داشته باشد)
                if (isset($row['spouse_first_name']) && !empty($row['spouse_first_name'])) {
                    $spouseData = $this->extractMemberData($row, 'spouse_');
                    $validatedSpouse = $this->validateMemberData($spouseData, $index + 2, false);
                    
                    if ($validatedSpouse) {
                        $spouseData['relationship'] = 'spouse';
                        $familyService->addMember($family, $spouseData);
                    }
                }
                
                DB::commit();
                $this->results['success']++;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $this->results['failed']++;
                $this->results['errors'][] = "خطا در سطر " . ($index + 2) . ": " . $e->getMessage();
                Log::error('Family Import Error', [
                    'row' => $index + 2,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }

    /**
     * استخراج اطلاعات خانواده از سطر اکسل
     */
    protected function extractFamilyData(array $row): array
    {
        return [
            'address' => $row['address'] ?? null,
            'postal_code' => $row['postal_code'] ?? null,
            'housing_status' => $row['housing_status'] ?? null,
            'housing_description' => $row['housing_description'] ?? null,
            'additional_info' => $row['additional_info'] ?? null,
        ];
    }

    /**
     * استخراج اطلاعات عضو خانواده از سطر اکسل
     */
    protected function extractMemberData(array $row, string $prefix): array
    {
        return [
            'first_name' => $row[$prefix . 'first_name'] ?? null,
            'last_name' => $row[$prefix . 'last_name'] ?? null,
            'national_code' => $row[$prefix . 'national_code'] ?? null,
            'father_name' => $row[$prefix . 'father_name'] ?? null,
            'birth_date' => $row[$prefix . 'birth_date'] ?? null,
            'gender' => $row[$prefix . 'gender'] ?? null,
            'marital_status' => $row[$prefix . 'marital_status'] ?? null,
            'education' => $row[$prefix . 'education'] ?? null,
            'has_disability' => filter_var($row[$prefix . 'has_disability'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'has_chronic_disease' => filter_var($row[$prefix . 'has_chronic_disease'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'has_insurance' => filter_var($row[$prefix . 'has_insurance'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'insurance_type' => $row[$prefix . 'insurance_type'] ?? null,
            'special_conditions' => $row[$prefix . 'special_conditions'] ?? null,
            'occupation' => $row[$prefix . 'occupation'] ?? null,
            'is_employed' => filter_var($row[$prefix . 'is_employed'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'mobile' => $row[$prefix . 'mobile'] ?? null,
            'phone' => $row[$prefix . 'phone'] ?? null,
        ];
    }

    /**
     * اعتبارسنجی اطلاعات خانواده
     */
    protected function validateFamilyData(array $data, int $row): bool
    {
        $validator = Validator::make($data, [
            'address' => 'required|string',
            'postal_code' => 'nullable|string|max:10',
            'housing_status' => 'nullable|in:owner,tenant,relative,other',
        ], [
            'address.required' => 'آدرس الزامی است.',
            'housing_status.in' => 'وضعیت مسکن باید یکی از موارد مالک، مستأجر، اقوام یا سایر باشد.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->results['errors'][] = "خطا در سطر {$row}: {$error}";
            }
            return false;
        }

        return true;
    }

    /**
     * اعتبارسنجی اطلاعات عضو خانواده
     */
    protected function validateMemberData(array $data, int $row, bool $isHead): bool
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'national_code' => 'required|string|size:10|unique:members,national_code',
            'gender' => 'nullable|in:male,female',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'education' => 'nullable|in:illiterate,primary,secondary,diploma,associate,bachelor,master,doctorate',
        ];

        $messages = [
            'first_name.required' => 'نام الزامی است.',
            'last_name.required' => 'نام خانوادگی الزامی است.',
            'national_code.required' => 'کد ملی الزامی است.',
            'national_code.size' => 'کد ملی باید 10 رقم باشد.',
            'national_code.unique' => 'این کد ملی قبلاً ثبت شده است.',
            'gender.in' => 'جنسیت باید مرد یا زن باشد.',
            'marital_status.in' => 'وضعیت تأهل نامعتبر است.',
            'education.in' => 'تحصیلات نامعتبر است.',
        ];

        $prefix = $isHead ? 'سرپرست خانوار' : 'عضو خانواده';

        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->results['errors'][] = "خطا در سطر {$row} ({$prefix}): {$error}";
            }
            return false;
        }

        return true;
    }

    /**
     * دریافت نتایج پردازش
     */
    public function getResults(): array
    {
        return $this->results;
    }
} 