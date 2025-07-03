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

class InsuranceShareService
{
    /**
     * مرحله ۱: تخصیص سهم و ایجاد یک لاگ گروهی برای آن
     */
    public function allocate(Collection $families, array $shares, string $payerType, ?int $fundingSourceId = null): array
    {
        Log::info('🎯 Starting insurance share allocation', [
            'families_count' => $families->count(),
            'shares' => $shares,
            'payer_type' => $payerType,
            'funding_source_id' => $fundingSourceId
        ]);

        // اعتبارسنجی درصدهای سهام
        $totalPercentage = collect($shares)->sum('percentage');
        if (abs($totalPercentage - 100) > 0.01) {
            throw new \Exception("مجموع درصدهای سهام باید 100 درصد باشد. مجموع فعلی: {$totalPercentage}%");
        }

        $createdShares = [];
        $errors = [];

        DB::transaction(function () use ($families, $shares, &$createdShares, &$errors, $payerType, $fundingSourceId) {
            // ✅ Batch Insert برای family insurances
            $familyInsurancesData = [];
            foreach ($families as $family) {
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
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];

                        if ($fundingSourceId) {
                            $fundingSource = $this->getCachedFundingSource($fundingSourceId);
                            if ($fundingSource) {
                                $shareRecord['payer_name'] = $fundingSource->name;
                                if ($fundingSource->type === 'organization') {
                                    $shareRecord['payer_organization_id'] = $fundingSource->source_id;
                                } elseif ($fundingSource->type === 'user') {
                                    $shareRecord['payer_user_id'] = $fundingSource->source_id;
                                }

                                if (isset($shareData['payer_type_id'])) {
                                    $shareRecord['payer_type_id'] = $shareData['payer_type_id'];
                                }
                            }
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
        Log::info('🏥 شروع پردازش فایل اکسل بیمه', ['file_path' => $filePath]);

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
        $validData = [
            'family_codes' => [],
            'premium_amounts' => [],
            'errors' => []
        ];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            try {
                // بررسی کد خانوار
                if (!isset($row[0]) || empty(trim($row[0]))) {
                    $validData['errors'][] = "ردیف {$i}: کد خانوار خالی است";
                    continue;
                }

                $familyCode = trim($row[0]);

                // بررسی مبلغ بیمه
                if (!isset($row[6]) || empty(trim($row[6]))) {
                    $validData['errors'][] = "ردیف {$i} - خانوار {$familyCode}: مبلغ بیمه خالی است";
                    continue;
                }

                // تمیز کردن مبلغ
                $premiumString = str_replace([',', ' ', 'ریال', 'تومان'], '', trim($row[6]));
                $premiumAmount = is_numeric($premiumString) ? floatval($premiumString) : 0;

                if ($premiumAmount <= 0) {
                    $validData['errors'][] = "مبلغ بیمه نامعتبر برای خانوار {$familyCode}: {$premiumAmount}";
                    continue;
                }

                $validData['family_codes'][] = $familyCode;
                $validData['premium_amounts'][$familyCode] = $premiumAmount;

                Log::debug("✅ داده معتبر استخراج شد", [
                    'family_code' => $familyCode,
                    'premium_amount' => $premiumAmount
                ]);

            } catch (\Exception $e) {
                $validData['errors'][] = "خطا در پردازش ردیف {$i}: " . $e->getMessage();
                Log::error("❌ خطا در استخراج ردیف {$i}", ['error' => $e->getMessage()]);
            }
        }

        Log::info('📊 خلاصه استخراج داده‌ها', [
            'valid_families' => count($validData['family_codes']),
            'errors_count' => count($validData['errors'])
        ]);

        return $validData;
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

        DB::transaction(function () use ($validData, $families, $insurances, &$results, &$familyUpdates, &$insuranceUpdates, &$shareUpdates, &$newInsurances) {

            foreach ($validData['family_codes'] as $familyCode) {
                $premiumAmount = $validData['premium_amounts'][$familyCode];
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
                        'premium_amount' => $premiumAmount,
                        'status' => 'active',
                        'updated_at' => now()
                    ];

                    // به‌روزرسانی سهام
                    foreach ($insurance->shares as $share) {
                        $shareUpdates[] = [
                            'id' => $share->id,
                            'amount' => ($premiumAmount * $share->percentage) / 100,
                            'updated_at' => now()
                        ];
                    }

                    $results['updated']++;

                    Log::debug("📝 آماده‌سازی به‌روزرسانی بیمه", [
                        'insurance_id' => $insurance->id,
                        'family_code' => $familyCode,
                        'premium_amount' => $premiumAmount
                    ]);
                } else {
                    // ایجاد بیمه جدید
                    $newInsurances[] = [
                        'family_id' => $family->id,
                        'insurance_type' => 'تکمیلی',
                        'premium_amount' => $premiumAmount,
                        'start_date' => now(),
                        'end_date' => now()->addYear(),
                        'status' => 'active',
                        'payer_type' => 'mixed',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $results['created']++;

                    Log::debug("🆕 آماده‌سازی ایجاد بیمه جدید", [
                        'family_id' => $family->id,
                        'family_code' => $familyCode,
                        'premium_amount' => $premiumAmount
                    ]);
                }

                // آماده‌سازی به‌روزرسانی خانواده
                $familyUpdates[] = [
                    'id' => $family->id,
                    'wizard_status' => InsuranceWizardStep::INSURED->value,
                    'status' => 'insured',
                    'is_insured' => true,
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

        Log::info('✅ پردازش Batch تکمیل شد', [
            'processed' => $results['processed'],
            'created' => $results['created'],
            'updated' => $results['updated'],
            'skipped' => $results['skipped'],
            'total_amount' => $results['total_insurance_amount']
        ]);

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
        
            // گام ۱: ایجاد لاگ در جدول ShareAllocationLog برای حفظ سازگاری با کد قبلی
            $logData = [
                'user_id' => Auth::id(),
                'batch_id' => $batchId,
                'description' => 'ثبت نهایی بیمه از طریق آپلود فایل اکسل - ' . count($familyIds) . ' خانواده',
                'families_count' => count($familyIds),
                'family_ids' => json_encode($familyIds),
                'shares_data' => json_encode([
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
                ]),
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
                    'family_ids' => json_encode([]),
                    'shares_data' => json_encode(['error' => 'Failed to create detailed log']),
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
     * Parse Jalali or Gregorian date
     */
    private function parseJalaliOrGregorianDate($dateString)
    {
        // Add your date parsing logic here
        // This is a placeholder - implement based on your existing date parsing logic
        return now(); // Temporary return
    }
}
