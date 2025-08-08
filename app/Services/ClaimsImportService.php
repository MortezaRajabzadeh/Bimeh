<?php

namespace App\Services;

use App\Models\Family;
use App\Models\InsuranceAllocation;
use App\Services\InsuranceImportLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class ClaimsImportService
{
    /**
     * پردازش فایل اکسل خسارات
     */
    public function processClaimsExcel(string $filePath, string $originalFileName): array
    {
        try {
            // خواندن فایل اکسل
            $imported = Excel::toCollection(null, $filePath);

            if (!isset($imported[0]) || $imported[0]->isEmpty()) {
                throw new \Exception('فایل اکسل آپلود شده فاقد داده است یا ساختار آن صحیح نیست.');
            }

            $rows = $imported[0]->toArray();

            if (empty($rows) || count($rows) < 2) {
                throw new \Exception('فایل اکسل باید حداقل شامل یک ردیف هدر و یک ردیف داده باشد.');
            }

            // ایجاد لاگ ایمپورت
            $importLog = InsuranceImportLogger::createLog($originalFileName, count($rows) - 1);

            // استخراج و اعتبارسنجی داده‌ها
            $validData = $this->extractAndValidateClaimsData($rows, $originalFileName);

            // پردازش داده‌ها
            $results = $this->processClaimsData($validData);

            // به‌روزرسانی لاگ
            InsuranceImportLogger::completeLog($importLog, [
                'created_count' => $results['created'],
                'updated_count' => $results['updated'],
                'skipped_count' => $results['skipped'],
                'error_count' => count($results['errors']),
                'total_insurance_amount' => $results['total_claims_amount'],
                'family_codes' => $results['family_codes'],
                'created_family_codes' => $results['created_claims'] ?? [],
                'updated_family_codes' => $results['updated_claims'] ?? [],
            ]);

            Log::info('✅ پردازش فایل اکسل خسارات با موفقیت به پایان رسید', $results);

            return $results;

        } catch (\Exception $e) {
            Log::error('❌ خطا در پردازش فایل اکسل خسارات', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception('خطا در پردازش فایل اکسل: ' . $e->getMessage());
        }
    }

    /**
     * استخراج و اعتبارسنجی داده‌های خسارات
     */
    private function extractAndValidateClaimsData(array $rows, string $originalFileName): array
    {
        $data = [];
        $errors = [];
        $familyCodes = [];

        try {
            // شناسایی موقعیت ستون‌ها
            $hasParticipationColumns = false;
            if (isset($rows[0])) {
                $headerRow = array_map('trim', $rows[0]);
                $hasParticipationColumns = in_array('درصد مشارکت', $headerRow) || in_array('نام مشارکت کننده', $headerRow);
            }

            // تعیین موقعیت ستون‌ها بر اساس ساختار فایل
            $familyCodeIndex = 0;  // A: کد خانوار
            
            if ($hasParticipationColumns) {
                // در صورت وجود ستون‌های مشارکت (ساختار فعلی فایل)
                $insuranceTypeIndex = 17;  // R: نوع بیمه (ستون 18)
                $insuranceAmountIndex = 18;  // S: مبلغ بیمه (ستون 19) 
                $startDateIndex = 19;  // T: تاریخ شروع (ستون 20)
                $endDateIndex = 20;  // U: تاریخ پایان (ستون 21)
                $claimsAmountIndex = 21;  // V: ستون خسارت (ستون 22)
                $claimsPaidDateIndex = 22;  // W: تاریخ پرداخت خسارت (ستون 23)
            } else {
                // در صورت عدم وجود ستون‌های مشارکت
                $insuranceTypeIndex = 15;  // P: نوع بیمه
                $insuranceAmountIndex = 16;  // Q: مبلغ بیمه
                $startDateIndex = 17;  // R: تاریخ شروع
                $endDateIndex = 18;  // S: تاریخ پایان
                $claimsAmountIndex = 19;  // T: ستون خسارت
                $claimsPaidDateIndex = 20;  // U: تاریخ پرداخت خسارت
            }

            // لاگ موقعیت ستون‌ها برای دیباگ
            Log::info('موقعیت ستون‌های اکسل', [
                'has_participation_columns' => $hasParticipationColumns,
                'family_code_index' => $familyCodeIndex,
                'claims_amount_index' => $claimsAmountIndex,
                'claims_paid_date_index' => $claimsPaidDateIndex,
                'start_date_index' => $startDateIndex
            ]);
            
            // پردازش ردیف‌های اکسل
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $rowNumber = $i + 1;

                $familyCode = trim($row[$familyCodeIndex] ?? '');
                $insuranceType = trim($row[$insuranceTypeIndex] ?? '');
                $insuranceAmount = trim($row[$insuranceAmountIndex] ?? '');
                $startDate = trim($row[$startDateIndex] ?? '');
                $endDate = trim($row[$endDateIndex] ?? '');
                $claimsAmount = trim($row[$claimsAmountIndex] ?? '');
                $claimsPaidDate = trim($row[$claimsPaidDateIndex] ?? '');
                
                // لاگ داده‌های خوانده شده برای اولین ردیف
                if ($i === 1) {
                    Log::info('داده‌های اولین ردیف داده', [
                        'row_number' => $rowNumber,
                        'family_code' => $familyCode,
                        'insurance_type' => $insuranceType,
                        'claims_amount' => $claimsAmount,
                        'claims_paid_date' => $claimsPaidDate,
                        'start_date' => $startDate,
                        'raw_row_data' => array_slice($row, 0, 25) // نمایش 25 ستون اول
                    ]);
                }

                // بررسی خالی بودن سطر کامل
                if (empty($familyCode) && empty($claimsAmount)) {
                    continue;
                }

                // بررسی فیلدهای ضروری
                if (empty($familyCode)) {
                    $errors[] = "ردیف {$rowNumber}: کد خانوار خالی است";
                    continue;
                }

                // اگر مبلغ خسارت خالی باشد، ردیف را رد کنیم
                if (empty($claimsAmount)) {
                    continue;
                }

                // تمیز کردن مبلغ خسارت
                $cleanClaimsAmount = $this->cleanAmount($claimsAmount);
                if ($cleanClaimsAmount === null || $cleanClaimsAmount <= 0) {
                    $errors[] = "ردیف {$rowNumber}: مبلغ خسارت نامعتبر است: {$claimsAmount}";
                    continue;
                }

                // پردازش تاریخ پرداخت خسارت
                $parsedClaimsPaidDate = null;
                if (!empty($claimsPaidDate)) {
                    try {
                        $parsedClaimsPaidDate = $this->parseDate($claimsPaidDate);
                    } catch (\Exception $e) {
                        $errors[] = "ردیف {$rowNumber}: تاریخ پرداخت خسارت نامعتبر است: {$claimsPaidDate}";
                        continue;
                    }
                }

                // پردازش تاریخ صدور بیمه‌نامه (از ستون تاریخ شروع)
                $parsedIssueDate = null;
                if (!empty($startDate)) {
                    try {
                        $parsedIssueDate = $this->parseDate($startDate);
                    } catch (\Exception $e) {
                        // اگر تاریخ صدور نامعتبر باشد، آن را خالی بگذاریم
                        $parsedIssueDate = null;
                    }
                }

                // ذخیره داده‌های معتبر
                $data[] = [
                    'family_code' => $familyCode,
                    'insurance_type' => !empty($insuranceType) ? $insuranceType : null,
                    'claims_amount' => $cleanClaimsAmount,
                    'claims_paid_date' => $parsedClaimsPaidDate,
                    'issue_date' => $parsedIssueDate,
                    'description' => "خسارت ایمپورت شده از فایل {$originalFileName}",
                    'row_number' => $rowNumber
                ];

                $familyCodes[] = $familyCode;
            }

        } catch (\Exception $e) {
            $errors[] = "خطا در خواندن فایل اکسل: " . $e->getMessage();
        }

        return [
            'claims_data' => $data,
            'family_codes' => array_unique($familyCodes),
            'errors' => $errors
        ];
    }

    /**
     * پردازش داده‌های خسارات
     */
    private function processClaimsData(array $validData): array
    {
        $results = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => $validData['errors'],
            'family_codes' => $validData['family_codes'],
            'total_claims_amount' => 0,
            'created_claims' => [],
            'updated_claims' => [],
        ];

        if (empty($validData['claims_data'])) {
            return $results;
        }

        // دریافت خانواده‌ها
        $families = Family::whereIn('family_code', $validData['family_codes'])
            ->pluck('id', 'family_code')
            ->toArray();

        DB::transaction(function () use ($validData, $families, &$results) {
            foreach ($validData['claims_data'] as $claimData) {
                $familyCode = $claimData['family_code'];
                $familyId = $families[$familyCode] ?? null;

                if (!$familyId) {
                    $results['errors'][] = "ردیف {$claimData['row_number']}: خانوار با کد {$familyCode} یافت نشد";
                    $results['skipped']++;
                    continue;
                }

                // بررسی وجود خسارت مشابه
                $existingClaim = InsuranceAllocation::where('family_id', $familyId)
                    ->where('amount', $claimData['claims_amount'])
                    ->where('issue_date', $claimData['issue_date'])
                    ->first();

                if ($existingClaim) {
                    // به‌روزرسانی خسارت موجود
                    $existingClaim->update([
                        'paid_at' => $claimData['claims_paid_date'],
                        'description' => $claimData['description'],
                        'insurance_type' => $claimData['insurance_type'],
                    ]);

                    $results['updated']++;
                    $results['updated_claims'][] = $familyCode;
                } else {
                    // ایجاد خسارت جدید
                    InsuranceAllocation::create([
                        'family_id' => $familyId,
                        'amount' => $claimData['claims_amount'],
                        'issue_date' => $claimData['issue_date'],
                        'paid_at' => $claimData['claims_paid_date'],
                        'description' => $claimData['description'],
                        'insurance_type' => $claimData['insurance_type'],
                        'funding_transaction_id' => null,
                    ]);

                    $results['created']++;
                    $results['created_claims'][] = $familyCode;
                }

                $results['total_claims_amount'] += $claimData['claims_amount'];
            }
        });

        return $results;
    }

    /**
     * تمیز کردن مبلغ
     */
    private function cleanAmount($amount): ?float
    {
        if (is_null($amount) || $amount === '') {
            return null;
        }

        // حذف کاراکترهای غیرضروری و تبدیل ممیز فارسی به انگلیسی
        $cleanAmount = str_replace([',', '،', ' ', 'ریال', 'تومان'], '', trim($amount));
        
        // تبدیل اعداد فارسی به انگلیسی
        $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $cleanAmount = str_replace($persianNumbers, $englishNumbers, $cleanAmount);

        if (!is_numeric($cleanAmount)) {
            return null;
        }

        return floatval($cleanAmount);
    }

    /**
     * پردازش تاریخ
     */
    private function parseDate($dateString)
    {
        $dateString = trim($dateString);

        // الگوهای متداول تاریخ
        $patterns = [
            // الگوی جلالی: 1403/03/15
            '/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/' => function ($matches) {
                return Jalalian::fromFormat('Y/m/d', $matches[1] . '/' . $matches[2] . '/' . $matches[3])->toCarbon()->format('Y-m-d');
            },
            // الگوی جلالی: 1403-03-15
            '/^(\d{4})-(\d{1,2})-(\d{1,2})$/' => function ($matches) {
                return Jalalian::fromFormat('Y-m-d', $matches[1] . '-' . $matches[2] . '-' . $matches[3])->toCarbon()->format('Y-m-d');
            },
            // الگوی میلادی: 2024/06/04
            '/^(20\d{2})\/(\d{1,2})\/(\d{1,2})$/' => function ($matches) {
                return Carbon::createFromFormat('Y/m/d', $matches[1] . '/' . $matches[2] . '/' . $matches[3])->format('Y-m-d');
            },
            // الگوی میلادی: 2024-06-04
            '/^(20\d{2})-(\d{1,2})-(\d{1,2})$/' => function ($matches) {
                return Carbon::createFromFormat('Y-m-d', $matches[1] . '-' . $matches[2] . '-' . $matches[3])->format('Y-m-d');
            }
        ];

        foreach ($patterns as $pattern => $converter) {
            if (preg_match($pattern, $dateString, $matches)) {
                return $converter($matches);
            }
        }

        throw new \Exception("فرمت تاریخ نامعتبر: {$dateString}");
    }
}
