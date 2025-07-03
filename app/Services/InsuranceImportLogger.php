<?php

namespace App\Services;

use App\Models\InsuranceImportLog;
use App\Models\Insurance;
use App\Models\Family;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InsuranceImportLogger
{
    /**
     * ایجاد لاگ جدید برای ایمپورت اکسل
     *
     * @param string $fileName نام فایل
     * @param int $totalRows تعداد کل ردیف‌ها
     * @return \App\Models\InsuranceImportLog
     */
    public static function createLog($fileName, $totalRows = 0)
    {
        return InsuranceImportLog::create([
            'file_name' => $fileName,
            'user_id' => Auth::id(),
            'total_rows' => $totalRows,
            'status' => 'processing',
            'created_count' => 0,
            'updated_count' => 0,
            'skipped_count' => 0,
            'error_count' => 0,
        ]);
    }

    /**
     * به‌روزرسانی لاگ ایمپورت
     *
     * @param \App\Models\InsuranceImportLog $log لاگ ایمپورت
     * @param array $data داده‌های به‌روزرسانی
     * @return \App\Models\InsuranceImportLog
     */
    public static function updateLog(InsuranceImportLog $log, array $data)
    {
        $log->update([
            'status' => $data['status'] ?? $log->status,
            'message' => $data['message'] ?? $log->message,
            'created_count' => $data['created_count'] ?? $log->created_count,
            'updated_count' => $data['updated_count'] ?? $log->updated_count,
            'skipped_count' => $data['skipped_count'] ?? $log->skipped_count,
            'error_count' => $data['error_count'] ?? $log->error_count,
            'total_insurance_amount' => $data['total_insurance_amount'] ?? $log->total_insurance_amount,
            'family_codes' => $data['family_codes'] ?? $log->family_codes,
            'updated_family_codes' => $data['updated_family_codes'] ?? $log->updated_family_codes,
            'created_family_codes' => $data['created_family_codes'] ?? $log->created_family_codes,
            'errors' => $data['errors'] ?? $log->errors,
        ]);

        return $log;
    }

    /**
     * ثبت خطا در لاگ ایمپورت
     *
     * @param \App\Models\InsuranceImportLog $log لاگ ایمپورت
     * @param \Exception $exception خطای رخ داده
     * @return \App\Models\InsuranceImportLog
     */
    public static function logError(InsuranceImportLog $log, \Exception $exception)
    {
        $currentErrors = json_decode($log->errors ?? '[]', true);
        $currentErrors[] = [
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'time' => now()->toDateTimeString(),
        ];

        return self::updateLog($log, [
            'status' => 'failed',
            'message' => $exception->getMessage(),
            'error_count' => $log->error_count + 1,
            'errors' => json_encode($currentErrors),
        ]);
    }

    /**
     * ثبت ردیف در لاگ ایمپورت
     *
     * @param \App\Models\InsuranceImportLog $log لاگ ایمپورت
     * @param array $rowData داده‌های ردیف
     * @param string $status وضعیت ردیف (created, updated, skipped, error)
     * @param string|null $message پیام مرتبط با ردیف
     * @return \App\Models\InsuranceImportLog
     */
    public static function logRow(InsuranceImportLog $log, array $rowData, string $status, ?string $message = null)
    {
        $currentRowData = json_decode($log->row_data ?? '[]', true);
        $rowData['status'] = $status;
        $rowData['message'] = $message;
        $rowData['time'] = now()->toDateTimeString();
        $currentRowData[] = $rowData;

        // Update the appropriate counter based on status
        $counters = [
            'created' => $log->created_count,
            'updated' => $log->updated_count,
            'skipped' => $log->skipped_count,
            'error' => $log->error_count,
        ];

        if (isset($counters[$status])) {
            $counters[$status]++;
        }

        return $log->update([
            'row_data' => json_encode($currentRowData),
            'created_count' => $counters['created'],
            'updated_count' => $counters['updated'],
            'skipped_count' => $counters['skipped'],
            'error_count' => $counters['error'],
        ]);
    }

    /**
     * ثبت تکمیل ایمپورت
     *
     * @param \App\Models\InsuranceImportLog $log لاگ ایمپورت
     * @param array $summary خلاصه نتایج ایمپورت
     * @return \App\Models\InsuranceImportLog
     */
    public static function completeLog(InsuranceImportLog $log, array $summary = [])
    {
        return self::updateLog($log, array_merge([
            'status' => 'completed',
            'message' => 'ایمپورت با موفقیت انجام شد.',
        ], $summary));
    }
    
    /**
     * ایجاد لاگ ایمپورت بیمه برای خانواده
     *
     * @param array $data داده‌های مورد نیاز برای ثبت لاگ
     * @return \App\Models\InsuranceImportLog|null
     */
    public function createInsuranceImportLog(array $data): ?InsuranceImportLog
    {
        try {
            if (!isset($data['family_id'])) {
                Log::error('Family ID is required for insurance import log', $data);
                return null;
            }
            
            $family = Family::find($data['family_id']);
            if (!$family) {
                Log::error('Family not found for insurance import log', $data);
                return null;
            }
            
            // ساخت آرایه‌های کدهای خانواده برای ثبت در لاگ
            $familyCodes = [$family->family_code];
            
            // ثبت لاگ جدید
            $log = InsuranceImportLog::create([
                'file_name' => $data['file_name'] ?? 'آپلود اکسل خانواده',
                'user_id' => $data['imported_by'] ?? Auth::id(),
                'status' => 'completed',
                'message' => $data['description'] ?? 'ثبت بیمه از طریق آپلود اکسل خانواده',
                'created_count' => 1,
                'updated_count' => 0,
                'skipped_count' => 0,
                'error_count' => 0,
                'total_insurance_amount' => $data['amount'] ?? 0,
                'family_codes' => json_encode($familyCodes),
                'created_family_codes' => json_encode($familyCodes),
                'updated_family_codes' => json_encode([]),
                'errors' => json_encode([]),
                'source' => $data['source'] ?? 'excel_import',
                'members_count' => $data['members_count'] ?? 0,
            ]);
            
            Log::info('Insurance import log created successfully', [
                'log_id' => $log->id,
                'family_id' => $family->id,
                'family_code' => $family->family_code
            ]);
            
            return $log;
        } catch (\Exception $e) {
            Log::error('Error creating insurance import log', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            return null;
        }
    }
}
