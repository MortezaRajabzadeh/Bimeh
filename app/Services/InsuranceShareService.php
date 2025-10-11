<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyInsurance;
use App\Models\InsuranceShare;
use App\Models\ShareAllocationLog;
use App\Services\InsuranceImportLogger;
use App\Models\FamilyStatusLog;
use App\Enums\InsuranceWizardStep;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class InsuranceShareService
{
    /**
     * مرحله ۱: تخصیص سهم و ایجاد یک لاگ گروهی برای آن
     */
    public function allocate(Collection $families, array $shares, string $payerType, ?int $fundingSourceId = null): array
    {

        // اعتبارسنجی درصدهای سهام
        $totalPercentage = collect($shares)->sum('percentage');
        if (abs($totalPercentage - 100) > 0.01) {
            throw new \Exception("مجموع درصدهای سهام باید 100 درصد باشد. مجموع فعلی: {$totalPercentage}%");
        }

        $createdShares = [];
        $errors = [];
        $allocationLogId = null;

        DB::transaction(function () use ($families, $shares, &$createdShares, &$errors, $payerType, $fundingSourceId, &$allocationLogId) {
            // ایجاد ShareAllocationLog برای tracking bulk allocation
            $allocationLog = ShareAllocationLog::create([
                'user_id' => Auth::id(),
                'batch_id' => 'allocation_' . time() . '_' . uniqid(),
                'description' => 'تخصیص سهم برای ' . count($families) . ' خانواده',
                'families_count' => count($families),
                'family_ids' => $families->pluck('id')->toArray(),
                'shares_data' => [], // مقدار پیش‌فرض
                'status' => 'pending',
                'total_amount' => 0, // موقتاً
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $allocationLogId = $allocationLog->id;
            // ✅ Batch Insert برای family insurances
            $familyInsurancesData = [];
            foreach ($families as $family) {
                // در متد allocate، هنگام ایجاد FamilyInsurance
                $familyInsurancesData[] = [
                    'family_id' => $family->id,
                    'insurance_type' => 'تکمیلی',
                    'premium_amount' => 0,
                    'start_date' => now(),
                    'end_date' => now()->addYear(),
                    'status' => 'awaiting_upload',
                    'payer_type' => $payerType,
                    'funding_source_id' => $fundingSourceId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                // و بعد از ایجاد FamilyInsurance، خانواده را آپدیت کنید:
                Family::whereIn('id', $families->pluck('id'))->update([
                    'insurance_id' => Auth::user()->organization_id // ✅ ست کردن organization_id
                ]);
            }

            // Batch insert family insurances
            if (!empty($familyInsurancesData)) {
                FamilyInsurance::insert($familyInsurancesData);
            }

            // ✅ دریافت IDs با یک کوئری
            $familyInsurances = FamilyInsurance::whereIn('family_id', $families->pluck('id'))
                ->where('status', 'awaiting_upload')
                ->latest()
                ->get()
                ->keyBy('family_id');

            // ✅ Batch Insert برای shares
            $sharesData = [];
            foreach ($families as $family) {
                $insurance = $familyInsurances[$family->id] ?? null;
                if (!$insurance) continue;

                foreach ($shares as $shareData) {
                    if ($shareData['percentage'] > 0) {
                        $shareRecord = [
                            'family_insurance_id' => $insurance->id,
                            'percentage' => $shareData['percentage'],
                            'amount' => 0,
                            'import_log_id' => $allocationLogId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        
                        // تنظیم اطلاعات پرداخت‌کننده - استفاده از funding_source_id مربوط به هر سهم
                        $currentFundingSourceId = null;
                        
                        // اولویت با funding_source_id موجود در shareData
                        if (isset($shareData['funding_source_id']) && !empty($shareData['funding_source_id'])) {
                            $currentFundingSourceId = (int)$shareData['funding_source_id'];
                        } elseif ($fundingSourceId) {
                            // fallback به پارامتر کلی
                            $currentFundingSourceId = $fundingSourceId;
                        }
                        
                        if ($currentFundingSourceId) {
                            $fundingSource = $this->getCachedFundingSource($currentFundingSourceId);
                            if ($fundingSource) {
                                // همیشه نام منبع مالی را در payer_name ذخیره کن
                                $shareRecord['payer_name'] = $fundingSource->name;
                                $shareRecord['funding_source_id'] = $fundingSource->id;
                                
                                // فقط اگر نوع منبع "person" است، اطلاعات کاربر و سازمان را ثبت کن
                                if ($fundingSource->type === 'person') {
                                    $shareRecord['payer_user_id'] = Auth::user()->id;
                                    $shareRecord['payer_organization_id'] = Auth::user()->organization_id;
                                }
                                // برای سایر انواع منابع (مثل bank)، فقط payer_name کافی است
                                
                                // تنظیم payer_type_id اگر در shares موجود باشد
                                if (isset($shareData['payer_type_id'])) {
                                    $shareRecord['payer_type_id'] = $shareData['payer_type_id'];
                                }
                                
                                // لاگ برای دیباگ
                                Log::info('InsuranceShareService::allocate - تنظیم منبع مالی', [
                                    'funding_source_id' => $fundingSource->id,
                                    'funding_source_name' => $fundingSource->name,
                                    'shareData_funding_source_id' => $shareData['funding_source_id'] ?? 'not_set',
                                    'percentage' => $shareData['percentage']
                                ]);
                            } else {
                                Log::warning('InsuranceShareService::allocate - منبع مالی با ID ' . $currentFundingSourceId . ' یافت نشد');
                            }
                        } else {
                            Log::warning('InsuranceShareService::allocate - هیچ funding_source_id معتبری برای سهم یافت نشد');
                        }

                        $sharesData[] = $shareRecord;
                    }
                }
                $currentStep = $family->wizard_status ?? InsuranceWizardStep::REVIEWING;
                $nextStep = InsuranceWizardStep::APPROVED; // مرحله بعد از تخصیص سهم

                $family->update([
                    'wizard_status' => $nextStep->value,
                    'status' => $nextStep->legacyStatus(), // وضعیت قدیمی برای سازگاری
                ]);
            }

            if (!empty($sharesData)) {
                InsuranceShare::insert($sharesData);
                $createdShares = InsuranceShare::whereIn('family_insurance_id', $familyInsurances->pluck('id'))->get();
                
                // به‌روزرسانی ShareAllocationLog پس از ایجاد سهم‌ها
                $totalAmount = $createdShares->sum('amount');
                ShareAllocationLog::where('id', $allocationLogId)->update([
                    'status' => 'completed',
                    'total_amount' => $totalAmount,
                    'shares_data' => $createdShares->toArray(),
                    'updated_at' => now()
                ]);
            }

            if (!empty($errors)) {
                throw new \Exception("خطا در حین پردازش تخصیص سهم رخ داد.");
            }
        });

        return [
            'shares' => $createdShares,
            'created_shares_count' => count($createdShares),
            'errors' => $errors,
        ];
    }

    /**
     * ✅ کش کردن funding sources
     */
    private function getCachedFundingSource(int $fundingSourceId)
    {
        return Cache::remember("funding_source_{$fundingSourceId}", 3600, function () use ($fundingSourceId) {
            return \App\Models\FundingSource::find($fundingSourceId);
        });
    }

    /**
     * Get family insurances for processing
     */
    public function getFamilyInsurances(Collection $families): Collection
    {
        return FamilyInsurance::whereIn('family_id', $families->pluck('id'))
            ->where('status', '!=', 'mixed')
            ->where('premium_amount', '>', 0)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->get();
    }

    /**
     * Stage 2: Complete insurance from Excel upload - نسخه بهینه‌سازی شده
     *
     * @param string $filePath مسیر فایل اکسل آپلود شده
     * @return array نتایج پردازش
     * @throws \Exception در صورت بروز خطا
     */
    public function completeInsuranceFromExcel(string $filePath): array
    {

        try {
            // خواندن فایل اکسل
            $imported = Excel::toCollection(null, $filePath);

            if (!isset($imported[0]) || $imported[0]->isEmpty()) {
                throw new \Exception('فایل اکسل آپلود شده فاقد داده است یا ساختار آن صحیح نیست.');
            }

            $rows = $imported[0]->toArray();

            // لاگ محتوای فایل برای دیباگ
            Log::info('📋 محتوای فایل اکسل', [
                'total_rows' => count($rows),
                'first_3_rows' => array_slice($rows, 0, 3)
            ]);

            // بررسی ساختار داده‌های اکسل
            if (empty($rows) || count($rows) < 2) {
                throw new \Exception('فایل اکسل باید حداقل شامل یک ردیف هدر و یک ردیف داده باشد.');
            }

            // ✅ STEP 1: استخراج و اعتبارسنجی داده‌ها
            $validData = $this->extractAndValidateExcelData($rows);

            // ✅ STEP 2: Batch Loading خانواده‌ها
            $families = $this->batchLoadFamilies($validData['family_codes']);

            // ✅ STEP 3: Batch Loading بیمه‌ها
            $insurances = $this->batchLoadInsurances($families->pluck('id'));

            // ✅ STEP 4: پردازش Batch
            $results = $this->processBatchData($validData, $families, $insurances);

            // ✅ STEP 5: ثبت لاگ
            $this->createInsuranceImportLog($results);

            Log::info('✅ پردازش فایل اکسل بیمه با موفقیت به پایان رسید', $results);

            return $results;

        } catch (\Exception $e) {
            Log::error('❌ خطا در پردازش فایل اکسل بیمه', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('خطا در پردازش فایل اکسل: ' . $e->getMessage());
        }
    }

    /**
     * ✅ استخراج و اعتبارسنجی داده‌های اکسل
     */
    private function extractAndValidateExcelData(array $rows): array
    {
        $data = [];
        $errors = [];
        $familyCodes = [];
        $premiumAmounts = [];
        $insuranceTypes = [];
        $startDates = [];
        $endDates = [];
        $policyNumbers = [];
        $notes = [];

        try {
            // شناسایی موقعیت ستون‌های مهم بر اساس اینکه آیا درصد مشارکت وجود دارد یا خیر
            // اگر ردیف اول (هدر) شامل "درصد مشارکت" باشد، یعنی از تب approved آمده
            $hasParticipationColumns = false;
            if (isset($rows[0])) {
                $headerRow = array_map('trim', $rows[0]);
                $hasParticipationColumns = in_array('درصد مشارکت', $headerRow) || in_array('نام مشارکت کننده', $headerRow);
                
                Log::info('🔍 تحلیل ساختار فایل اکسل', [
                    'has_participation_columns' => $hasParticipationColumns ? 'yes' : 'no',
                    'header_columns' => count($headerRow),
                    'sample_headers' => array_slice($headerRow, 0, 5)
                ]);
            }
            
            /**
             * ساختار فایل اکسل:
             * 
             * فایل بدون مشارکت (19 ستون): A-S
             * - A-K (0-10): کد خانوار، کد ملی سرپرست، سرپرست، نام عضو، کد ملی عضو، نسبت، تاریخ تولد، جنسیت، معیار پذیرش، مدرک، تاریخ عضویت
             * - L-O (11-14): استان، شهرستان، دهستان، سازمان
             * - P-S (15-18): نوع بیمه، مبلغ بیمه، تاریخ شروع، تاریخ پایان
             * 
             * فایل با مشارکت (21 ستون): A-U
             * - A-K (0-10): اطلاعات خانواده و اعضا
             * - L-M (11-12): درصد مشارکت، نام مشارکت کننده
             * - N-Q (13-16): استان، شهرستان، دهستان، سازمان
             * - R-U (17-20): نوع بیمه، مبلغ بیمه، تاریخ شروع، تاریخ پایان
             */
            
            // تعیین موقعیت ستون‌ها بر اساس نوع فایل
            $familyCodeIndex = 0;        // A: کد خانوار
            $headNationalCodeIndex = 1;  // B: کد ملی سرپرست
            
            if ($hasParticipationColumns) {
                // فایل دارای ستون‌های مشارکت (21 ستون)
                // بر اساس لاگ: 17=نوعبیمه, 18=مبلغ, 19=شروع, 20=پایان
                $insuranceTypeIndex = 17;   // R: نوع بیمه
                $insuranceAmountIndex = 18; // S: مبلغ بیمه
                $startDateIndex = 19;       // T: تاریخ شروع
                $endDateIndex = 20;         // U: تاریخ پایان
            } else {
                // فایل بدون ستون‌های مشارکت (19 ستون)
                // ساختار فایل:
                // A-K (0-10): اطلاعات خانواده و اعضا (11 ستون)
                // L-O (11-14): استان، شهرستان، دهستان، سازمان (4 ستون)
                // P-S (15-18): نوع بیمه، مبلغ بیمه، تاریخ شروع، تاریخ پایان (4 ستون)
                $insuranceTypeIndex = 15;    // P: نوع بیمه (تغییر از 13 به 15)
                $insuranceAmountIndex = 16;  // Q: مبلغ بیمه (تغییر از 14 به 16)
                $startDateIndex = 17;        // R: تاریخ شروع (تغییر از 15 به 17)
                $endDateIndex = 18;          // S: تاریخ پایان (تغییر از 16 به 18)
            }

            // پردازش ردیف‌های اکسل (شروع از ردیف دوم - ردیف اول هدر است)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rowNumber = $i + 1; // شماره ردیف واقعی در اکسل
                
                // خواندن داده‌های ردیف
                $familyCode = trim($row[$familyCodeIndex] ?? '');
                $headNationalCode = trim($row[$headNationalCodeIndex] ?? '');
                $insuranceType = trim($row[$insuranceTypeIndex] ?? '');
                $insuranceAmount = trim($row[$insuranceAmountIndex] ?? '');
                $startDate = trim($row[$startDateIndex] ?? '');
                $endDate = trim($row[$endDateIndex] ?? '');
                $policyNumber = '';  // شماره بیمه‌نامه در ساختار جدید نداریم
                $noteText = '';     // توضیحات در ساختار جدید نداریم

                // لاگ‌گذاری دقیق برای دیباگ
                Log::debug("📋 پردازش ردیف {$rowNumber}", [
                    'row_number' => $rowNumber,
                    'family_code' => $familyCode,
                    'head_national_code' => $headNationalCode,
                    'insurance_type' => $insuranceType,
                    'insurance_type_index' => $insuranceTypeIndex,
                    'insurance_amount' => $insuranceAmount,
                    'insurance_amount_index' => $insuranceAmountIndex,
                    'start_date' => $startDate,
                    'start_date_index' => $startDateIndex,
                    'end_date' => $endDate,
                    'end_date_index' => $endDateIndex,
                    'has_participation_columns' => $hasParticipationColumns ? 'yes' : 'no',
                    'total_columns_in_row' => count($row)
                ]);

                // بررسی خالی بودن سطر کامل (اگر همه فیلدهای اصلی خالی باشند، سطر را رد کن)
                if (empty($familyCode) && empty($insuranceType) && empty($insuranceAmount)) {
                    Log::debug("ردیف {$rowNumber} خالی است، رد می‌شود");
                    continue;
                }

                // بررسی خالی بودن فیلدهای ضروری
                if (empty($familyCode)) {
                    $errors[] = "ردیف {$rowNumber}: کد خانوار خالی است (ستون A)";
                    Log::warning("⚠️ ردیف {$rowNumber}: کد خانوار خالی", ['row_data' => $row]);
                    continue;
                }

                if (empty($insuranceType)) {
                    $errors[] = "ردیف {$rowNumber}: نوع بیمه خالی است (ستون " . chr(65 + $insuranceTypeIndex) . ", ایندکس {$insuranceTypeIndex})";
                    Log::warning("⚠️ ردیف {$rowNumber}: نوع بیمه خالی", [
                        'expected_column' => chr(65 + $insuranceTypeIndex),
                        'index' => $insuranceTypeIndex,
                        'value_found' => $row[$insuranceTypeIndex] ?? 'NULL'
                    ]);
                    continue;
                }

                if (empty($insuranceAmount)) {
                    $errors[] = "ردیف {$rowNumber}: مبلغ بیمه خالی است (ستون " . chr(65 + $insuranceAmountIndex) . ", ایندکس {$insuranceAmountIndex})";
                    Log::warning("⚠️ ردیف {$rowNumber}: مبلغ بیمه خالی", [
                        'expected_column' => chr(65 + $insuranceAmountIndex),
                        'index' => $insuranceAmountIndex,
                        'value_found' => $row[$insuranceAmountIndex] ?? 'NULL'
                    ]);
                    continue;
                }

                // تشخیص نوع بیمه
                $normalizedInsuranceType = $this->normalizeInsuranceType($insuranceType);
                if (!$normalizedInsuranceType) {
                    $errors[] = "ردیف {$rowNumber}: نوع بیمه نامعتبر است: '{$insuranceType}' (ستون " . chr(65 + $insuranceTypeIndex) . "). مقادیر معتبر: 'تکمیلی' یا 'تامین اجتماعی'";
                    Log::warning("⚠️ ردیف {$rowNumber}: نوع بیمه نامعتبر", [
                        'value' => $insuranceType,
                        'column' => chr(65 + $insuranceTypeIndex),
                        'valid_values' => ['تکمیلی', 'تامین اجتماعی']
                    ]);
                    continue;
                }

                // تمیز کردن مبلغ بیمه
                $cleanAmount = $this->cleanInsuranceAmount($insuranceAmount);
                if ($cleanAmount === null) {
                    $errors[] = "ردیف {$rowNumber}: مبلغ بیمه نامعتبر است: '{$insuranceAmount}' (ستون " . chr(65 + $insuranceAmountIndex) . "). مبلغ باید بین 1,000 تا 100,000,000 ریال باشد";
                    Log::warning("⚠️ ردیف {$rowNumber}: مبلغ بیمه نامعتبر", [
                        'value' => $insuranceAmount,
                        'column' => chr(65 + $insuranceAmountIndex),
                        'valid_range' => '1,000 - 100,000,000 ریال'
                    ]);
                    continue;
                }

                // پردازش تاریخ‌ها
                $parsedStartDate = null;
                $parsedEndDate = null;

                if (!empty($startDate)) {
                    try {
                        $parsedStartDate = $this->parseJalaliOrGregorianDate($startDate);
                    } catch (\Exception $e) {
                        $errors[] = "ردیف {$rowNumber}: تاریخ شروع نامعتبر است: {$startDate}";
                        continue;
                    }
                }

                if (!empty($endDate)) {
                    try {
                        $parsedEndDate = $this->parseJalaliOrGregorianDate($endDate);
                    } catch (\Exception $e) {
                        $errors[] = "ردیف {$rowNumber}: تاریخ پایان نامعتبر است: {$endDate}";
                        continue;
                    }
                }

                // ذخیره داده‌های معتبر
                $familyCodes[] = $familyCode;
                $premiumAmounts[$familyCode] = $cleanAmount;
                $insuranceTypes[$familyCode] = $normalizedInsuranceType;
                $startDates[$familyCode] = $parsedStartDate;
                $endDates[$familyCode] = $parsedEndDate;
                $policyNumbers[$familyCode] = $policyNumber;
                $notes[$familyCode] = $noteText;
            }

        } catch (\Exception $e) {
            $errors[] = "خطا در خواندن فایل اکسل: " . $e->getMessage();
        }

        // لاگ خلاصه نتایج اعتبارسنجی
        Log::info('✅ اعتبارسنجی فایل اکسل تکمیل شد', [
            'total_rows_processed' => count($rows) - 1, // منهای ردیف هدر
            'valid_families' => count(array_unique($familyCodes)),
            'errors_count' => count($errors),
            'has_participation_columns' => $hasParticipationColumns ?? false,
            'column_indices' => [
                'insurance_type' => $insuranceTypeIndex ?? 'N/A',
                'insurance_amount' => $insuranceAmountIndex ?? 'N/A',
                'start_date' => $startDateIndex ?? 'N/A',
                'end_date' => $endDateIndex ?? 'N/A'
            ]
        ]);

        if (!empty($errors)) {
            Log::warning('⚠️ خطاهای اعتبارسنجی یافت شد', [
                'errors' => $errors
            ]);
        }

        return [
            'family_codes' => array_unique($familyCodes),
            'premium_amounts' => $premiumAmounts,
            'insurance_types' => $insuranceTypes,
            'start_dates' => $startDates,
            'end_dates' => $endDates,
            'policy_numbers' => $policyNumbers,
            'notes' => $notes,
            'errors' => $errors
        ];
    }

    /**
     * ✅ Batch Loading خانواده‌ها با کش
     */
    private function batchLoadFamilies(array $familyCodes): Collection
    {
        if (empty($familyCodes)) {
            return collect();
        }

        // ابتدا از کش چک کنیم
        $cacheKey = 'families_by_codes_' . md5(implode(',', $familyCodes));

        return Cache::remember($cacheKey, 1800, function () use ($familyCodes) {
            Log::info('🔍 بارگذاری خانواده‌ها از دیتابیس', [
                'family_codes_count' => count($familyCodes),
                'cache_key' => 'families_by_codes_' . md5(implode(',', $familyCodes))
            ]);

            return Family::whereIn('family_code', $familyCodes)
                ->select(['id', 'family_code', 'status', 'wizard_status'])
                ->get()
                ->keyBy('family_code');
        });
    }

    /**
     * ✅ Batch Loading بیمه‌ها
     */
    private function batchLoadInsurances(Collection $familyIds): Collection
    {
        if ($familyIds->isEmpty()) {
            return collect();
        }

        Log::info('🔍 بارگذاری بیمه‌ها از دیتابیس', [
            'family_ids_count' => $familyIds->count()
        ]);

        return FamilyInsurance::with(['shares:id,family_insurance_id,percentage,amount'])
            ->whereIn('family_id', $familyIds)
            ->select(['id', 'family_id', 'premium_amount', 'status', 'insurance_type'])
            ->get()
            ->groupBy('family_id');
    }

    /**
     * ✅ پردازش Batch داده‌ها
     */
    private function processBatchData(array $validData, Collection $families, Collection $insurances): array
    {
        $results = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => $validData['errors'],
            'family_codes' => [],
            'total_insurance_amount' => 0,
        ];

        $familyUpdates = [];
        $insuranceUpdates = [];
        $shareUpdates = [];
        $newInsurances = [];
        $allocationLogId = null;

        DB::transaction(function () use ($validData, $families, $insurances, &$results, &$familyUpdates, &$insuranceUpdates, &$shareUpdates, &$newInsurances, &$allocationLogId) {
            // ایجاد ShareAllocationLog برای tracking bulk allocation
            $allocationLog = ShareAllocationLog::create([
                'user_id' => Auth::id(),
                'batch_id' => 'excel_import_' . time() . '_' . uniqid(),
                'description' => 'به‌روزرسانی سهام از فایل اکسل',
                'families_count' => count($validData['family_codes']),
                'family_ids' => $families->pluck('id')->toArray(),
                'shares_data' => [], // مقدار پیش‌فرض
                'status' => 'pending',
                'total_amount' => 0, // موقتاً
                'created_at' => now(),
                'updated_at' => now()
            ]);
            $allocationLogId = $allocationLog->id;

            // در متد processBatchData
            foreach ($validData['family_codes'] as $familyCode) {
                $premiumAmount = $validData['premium_amounts'][$familyCode];
                $insuranceType = $validData['insurance_types'][$familyCode];
                $startDate = $validData['start_dates'][$familyCode];
                $endDate = $validData['end_dates'][$familyCode];
                $family = $families->get($familyCode);

                if (!$family) {
                    $results['errors'][] = "خانوار با کد {$familyCode} یافت نشد";
                    $results['skipped']++;
                    continue;
                }

                $familyInsurances = $insurances->get($family->id, collect());
                $insurance = $familyInsurances->first();

                if ($insurance) {
                    // به‌روزرسانی بیمه موجود
                    $insuranceUpdates[] = [
                        'id' => $insurance->id,
                        'insurance_type' => $insuranceType,
                        'premium_amount' => $premiumAmount,
                        'start_date' => $startDate,
                        'end_date' => $endDate,
                        'status' => 'insured',
                        'updated_at' => now()
                    ];

                    // به‌روزرسانی سهام
                    foreach ($insurance->shares as $share) {
                        $shareUpdates[] = [
                            'id' => $share->id,
                            'amount' => ($premiumAmount * $share->percentage) / 100,
                            'import_log_id' => $allocationLogId,
                            'updated_at' => now()
                        ];
                    }

                    $results['updated']++;
                } else {
                    // ایجاد بیمه جدید - فقط یک بار!
                    $newInsurances[] = [
                        'family_id' => $family->id,
                        'insurance_type' => $insuranceType,
                        'premium_amount' => $premiumAmount,
                        'start_date' => $startDate ?: now()->format('Y-m-d'),
                        'end_date' => $endDate ?: now()->addYear()->format('Y-m-d'),
                        'status' => 'insured',
                        'payer_type' => 'mixed',
                        'funding_source_id' => null, // Default to null since $fundingSourceId is not defined
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $results['created']++;
                }

                // آماده‌سازی به‌روزرسانی خانواده
                $familyUpdates[] = [
                    'id' => $family->id,
                    'insurance_id' => Auth::user()->organization_id, // ✅ اضافه شد
                    'wizard_status' => InsuranceWizardStep::INSURED->value,
                    'status' => InsuranceWizardStep::INSURED->legacyStatus(), // Set status to insured legacy status
                    'is_insured' => true, // Set to true since we're processing insurance data
                    'updated_at' => now()
                ];

                $results['processed']++;
                $results['family_codes'][] = $familyCode;
                $results['total_insurance_amount'] += $premiumAmount;
            }

            // ✅ Batch Updates
            if (!empty($familyUpdates)) {
                Log::info('📝 اجرای batch update خانواده‌ها', ['count' => count($familyUpdates)]);
                $this->batchUpdateFamilies($familyUpdates);
            }

            if (!empty($insuranceUpdates)) {
                Log::info('📝 اجرای batch update بیمه‌ها', ['count' => count($insuranceUpdates)]);
                $this->batchUpdateInsurances($insuranceUpdates);
            }

            if (!empty($shareUpdates)) {
                Log::info('📝 اجرای batch update سهام', ['count' => count($shareUpdates)]);
                $this->batchUpdateShares($shareUpdates);
            }

            if (!empty($newInsurances)) {
                Log::info('🆕 اجرای batch insert بیمه‌های جدید', ['count' => count($newInsurances)]);
                FamilyInsurance::insert($newInsurances);
            }
        });



        return $results;
    }

    /**
     * ✅ Batch Update خانواده‌ها
     */
    private function batchUpdateFamilies(array $updates): void
    {
        if (empty($updates)) return;

        // گروه‌بندی بر اساس مقادیر یکسان برای bulk update
        $groupedUpdates = collect($updates)->groupBy(function($update) {
            return $update['wizard_status'] . '|' . $update['status'];
        });

        foreach ($groupedUpdates as $group) {
            $ids = $group->pluck('id')->toArray();
            $firstUpdate = $group->first();

            Family::whereIn('id', $ids)->update([
                    'insurance_id' => $firstUpdate['insurance_id'] ?? null, // ✅ اضافه شد
                'wizard_status' => $firstUpdate['wizard_status'],
                'status' => $firstUpdate['status'],
                'is_insured' => $firstUpdate['is_insured'],
                'updated_at' => now()
            ]);
        }
    }
    /**
     * ✅ Batch Update بیمه‌ها
     */
    private function batchUpdateInsurances(array $updates): void
    {
        foreach ($updates as $update) {
            FamilyInsurance::where('id', $update['id'])->update([
                'premium_amount' => $update['premium_amount'],
                'status' => $update['status'],
                'updated_at' => $update['updated_at']
            ]);
        }

        Log::debug('✅ Batch update بیمه‌ها تکمیل شد', ['updated_count' => count($updates)]);
    }

    /**
     * ✅ Batch Update سهام
     */
    private function batchUpdateShares(array $updates): void
    {
        foreach ($updates as $update) {
            InsuranceShare::where('id', $update['id'])->update([
                'amount' => $update['amount'],
                'import_log_id' => $update['import_log_id'],
                'updated_at' => $update['updated_at']
            ]);
        }

        Log::debug('✅ Batch update سهام تکمیل شد', ['updated_count' => count($updates)]);
    }

    /**
     * ✅ ایجاد لاگ تخصیص سهم
     */
    private function createInsuranceImportLog(array $results): void
    {
        if (empty($results['family_codes'])) {
            Log::warning('⚠️ هیچ خانواده‌ای برای ثبت لاگ وجود ندارد');
            return;
        }

        try {
            // جمع‌آوری IDهای خانواده‌ها
            $familyIds = Family::whereIn('family_code', $results['family_codes'])
                ->pluck('id')
                ->toArray();

            if (empty($familyIds)) {
                Log::warning('⚠️ IDهای خانواده یافت نشد');
                return;
            }

            $batchId = 'excel_upload_' . time() . '_' . uniqid();
            $fileName = isset($results['file_name']) ? $results['file_name'] : 'excel_upload_' . date('Y-m-d_H-i-s') . '.xlsx';
            
            // محاسبه file_hash
            $fileHash = hash('sha256', $fileName . $results['total_insurance_amount'] . implode(',', $results['family_codes']));
            
            // بررسی تکراری بودن
            if (ShareAllocationLog::isDuplicateByFileHash($fileHash)) {
                Log::warning('⚠️ فایل تکراری شناسایی شد', ['file_hash' => $fileHash]);
                throw new \Exception('این فایل قبلاً پردازش شده است. لطفاً از تکرار آپلود خودداری کنید.');
            }
        
            // گام ۱: ایجاد لاگ در جدول ShareAllocationLog برای حفظ سازگاری با کد قبلی
            $logData = [
                'user_id' => Auth::id(),
                'batch_id' => $batchId,
                'description' => 'ثبت نهایی بیمه از طریق آپلود فایل اکسل - ' . count($familyIds) . ' خانواده',
                'families_count' => count($familyIds),
                'family_ids' => $familyIds,
                'file_hash' => $fileHash,
                'shares_data' => [
                    'upload_method' => 'excel',
                    'processed_families' => count($familyIds),
                    'created' => $results['created'],
                    'updated' => $results['updated'],
                    'skipped' => $results['skipped'],
                    'errors_count' => count($results['errors']),
                    'upload_date' => now()->format('Y-m-d H:i:s'),
                    'file_processing_summary' => [
                        'total_processed' => $results['processed'],
                        'successful_operations' => $results['created'] + $results['updated'],
                        'failed_operations' => $results['skipped']
                    ]
                ],
                'total_amount' => $results['total_insurance_amount'],
                'status' => 'completed'
            ];

            $newLog = ShareAllocationLog::create($logData);

            // گام ۲: ایجاد لاگ در جدول InsuranceImportLog با استفاده از سرویس InsuranceImportLogger
            $importLog = InsuranceImportLogger::createLog($fileName, $results['processed'] ?? 0);
        
            // به‌روزرسانی لاگ با اطلاعات کامل
            InsuranceImportLogger::completeLog($importLog, [
                'status' => 'completed',
                'message' => 'آپلود اکسل با موفقیت انجام شد',
                'created_count' => $results['created'] ?? 0,
                'updated_count' => $results['updated'] ?? 0,
                'skipped_count' => $results['skipped'] ?? 0,
                'error_count' => count($results['errors'] ?? []),
                'total_insurance_amount' => $results['total_insurance_amount'] ?? 0,
                'family_codes' => $results['family_codes'] ?? [], // کدهای خانواده‌های پردازش شده
                'created_family_codes' => $results['created_family_codes'] ?? [], // کدهای خانواده‌های جدید ایجاد شده
                'updated_family_codes' => $results['updated_family_codes'] ?? [], // کدهای خانواده‌های به‌روزرسانی شده
            ]);

            Log::info('✅ لاگ تخصیص سهم و ایمپورت با موفقیت ایجاد شد', [
                'share_log_id' => $newLog->id,
                'import_log_id' => $importLog->id,
                'batch_id' => $batchId,
                'families_count' => count($familyIds),
                'total_amount' => $results['total_insurance_amount'],
                'created' => $results['created'],
                'updated' => $results['updated'],
                'skipped' => $results['skipped']
            ]);

        } catch (\Exception $e) {
            Log::error('❌ خطا در ایجاد لاگ تخصیص سهم یا ایمپورت', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'results' => $results
            ]);

            // در صورت خطا در لاگ، حداقل یک لاگ ساده ایجاد کنیم
            try {
                // ایجاد لاگ fallback در ShareAllocationLog
                ShareAllocationLog::create([
                    'user_id' => Auth::id(),
                    'batch_id' => 'fallback_' . time(),
                    'description' => 'لاگ fallback برای آپلود اکسل',
                    'families_count' => $results['processed'] ?? 0,
                    'family_ids' => [],
                    'shares_data' => ['error' => 'Failed to create detailed log'],
                    'total_amount' => $results['total_insurance_amount'] ?? 0,
                    'status' => 'completed_with_errors'
                ]);
            
                // تلاش برای ایجاد لاگ fallback در InsuranceImportLog نیز
                $fileName = isset($results['file_name']) ? $results['file_name'] : 'fallback_excel_' . date('Y-m-d_H-i-s') . '.xlsx';
                $fallbackLog = InsuranceImportLogger::createLog($fileName, $results['processed'] ?? 0);
                InsuranceImportLogger::updateLog($fallbackLog, [
                    'status' => 'completed_with_errors',
                    'message' => 'ثبت با خطا مواجه شد: ' . $e->getMessage(),
                    'total_insurance_amount' => $results['total_insurance_amount'] ?? 0,
                ]);

                Log::info('✅ لاگ‌های fallback با موفقیت ایجاد شدند');
            } catch (\Exception $fallbackError) {
                Log::error('❌ حتی لاگ fallback نیز ناموفق بود', ['error' => $fallbackError->getMessage()]);
            }
        }
    }

    /**
     * Recalculate share amounts based on final premium
     */
    private function recalculateShareAmounts(int $familyInsuranceId, float $premiumAmount): void
    {
        $shares = InsuranceShare::where('family_insurance_id', $familyInsuranceId)->get();

        foreach ($shares as $share) {
            $amount = ($premiumAmount * $share->percentage) / 100;
            $share->update(['amount' => $amount]);

            Log::debug('محاسبه مجدد سهم بیمه', [
                'share_id' => $share->id,
                'percentage' => $share->percentage,
                'premium_amount' => $premiumAmount,
                'calculated_amount' => $amount
            ]);
        }
    }

    /**
     * تبدیل تاریخ جلالی یا میلادی به تاریخ کاربن
     */
    private function parseJalaliOrGregorianDate($dateString)
    {
        $dateString = trim($dateString);

        // الگوهای متداول تاریخ
        $patterns = [
            // الگوی جلالی: 1403/03/15
            '/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/' => function ($matches) {
                return Jalalian::fromFormat('Y/m/d', $matches[1] . '/' . $matches[2] . '/' . $matches[3])->toCarbon();
            },
            // الگوی جلالی: 1403-03-15
            '/^(\d{4})-(\d{1,2})-(\d{1,2})$/' => function ($matches) {
                return Jalalian::fromFormat('Y-m-d', $matches[1] . '-' . $matches[2] . '-' . $matches[3])->toCarbon();
            },
            // الگوی میلادی: 2024/06/04
            '/^(20\d{2})\/(\d{1,2})\/(\d{1,2})$/' => function ($matches) {
                return Carbon::createFromFormat('Y/m/d', $matches[1] . '/' . $matches[2] . '/' . $matches[3]);
            },
            // الگوی میلادی: 2024-06-04
            '/^(20\d{2})-(\d{1,2})-(\d{1,2})$/' => function ($matches) {
                return Carbon::createFromFormat('Y-m-d', $matches[1] . '-' . $matches[2] . '-' . $matches[3]);
            }
        ];

        foreach ($patterns as $pattern => $converter) {
            if (preg_match($pattern, $dateString, $matches)) {
                return $converter($matches);
            }
        }

        throw new \Exception("فرمت تاریخ نامعتبر: {$dateString}");
    }

    /**
     * تشخیص و تبدیل نوع بیمه
     * 
     * این متد کاراکترهای نامعتبر را پاکسازی می‌کند و نوع بیمه را تشخیص می‌دهد.
     * مقاوم در برابر خطاهای تایپی مانند: "ـکمیلی" به جای "تکمیلی"
     */
    private function normalizeInsuranceType($insuranceType): ?string
    {
        $originalValue = $insuranceType;
        
        // حذف کاراکترهای نامعتبر (فقط حروف فارسی، انگلیسی و فاصله مجاز است)
        // Pattern: حروف فارسی (\x{0600}-\x{06FF}) + حروف انگلیسی (a-zA-Z) + فاصله (\s)
        $cleanedValue = preg_replace('/[^\x{0600}-\x{06FF}a-zA-Z\s]/u', '', $insuranceType);
        
        // لاگ در صورت وجود کاراکترهای نامعتبر
        if ($originalValue !== $cleanedValue) {
            Log::debug('🧹 پاکسازی نوع بیمه', [
                'original' => $originalValue,
                'cleaned' => $cleanedValue,
                'removed_chars' => array_values(array_diff(
                    mb_str_split($originalValue),
                    mb_str_split($cleanedValue)
                ))
            ]);
        }
        
        // تبدیل به حروف کوچک و حذف فاصله‌های اضافی
        $normalizedValue = trim(mb_strtolower($cleanedValue));
        
        // تعریف کلمات کلیدی
        $socialInsuranceKeywords = ['تامین اجتماعی', 'تامین', 'اجتماعی', 'social'];
        $supplementaryInsuranceKeywords = ['تکمیلی', 'کمیلی', 'supplementary', 'درمان', 'medical'];
        
        // بررسی تامین اجتماعی
        foreach ($socialInsuranceKeywords as $keyword) {
            if (mb_strpos($normalizedValue, mb_strtolower($keyword)) !== false) {
                Log::debug('✅ نوع بیمه تشخیص داده شد', [
                    'input' => $originalValue,
                    'detected' => 'تامین اجتماعی',
                    'matched_keyword' => $keyword
                ]);
                return 'تامین اجتماعی';
            }
        }
        
        // بررسی تکمیلی
        foreach ($supplementaryInsuranceKeywords as $keyword) {
            if (mb_strpos($normalizedValue, mb_strtolower($keyword)) !== false) {
                Log::debug('✅ نوع بیمه تشخیص داده شد', [
                    'input' => $originalValue,
                    'detected' => 'تکمیلی',
                    'matched_keyword' => $keyword
                ]);
                return 'تکمیلی';
            }
        }
        
        // اگر هیچ کدام تشخیص داده نشد
        Log::warning('❌ نوع بیمه قابل تشخیص نیست', [
            'original' => $originalValue,
            'cleaned' => $cleanedValue,
            'normalized' => $normalizedValue
        ]);
        
        return null;
    }

    /**
     * تمیز کردن مبلغ بیمه
     */
    private function cleanInsuranceAmount($amount): ?int
    {
        // حذف کاراکترهای غیرضروری
        $cleanAmount = preg_replace('/[^\d]/', '', $amount);
        
        if (empty($cleanAmount) || !is_numeric($cleanAmount)) {
            return null;
        }
        
        $numericAmount = (int) $cleanAmount;
        
        // بررسی محدوده منطقی
        if ($numericAmount < 1000 || $numericAmount > 100000000) {
            return null;
        }
        
        return $numericAmount;
    }
}
