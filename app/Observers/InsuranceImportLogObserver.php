<?php

namespace App\Observers;

use App\Models\InsuranceImportLog;
use App\Helpers\FinancialCacheHelper;
use Illuminate\Support\Facades\Log;

/**
 * Observer برای InsuranceImportLog
 * 
 * این Observer برای import‌های Excel است
 */
class InsuranceImportLogObserver
{
    protected FinancialCacheHelper $cacheHelper;

    /**
     * Constructor
     */
    public function __construct(FinancialCacheHelper $cacheHelper = null)
    {
        $this->cacheHelper = $cacheHelper ?? new FinancialCacheHelper();
    }

    /**
     * Handle the InsuranceImportLog "created" event.
     */
    public function created(InsuranceImportLog $log): void
    {
        $this->clearFinancialCache('created', $log);

        Log::info('📥 لاگ ایمپورت بیمه جدید - جزئیات', [
            'log_id' => $log->id,
            'file_name' => $log->file_name,
            'created_count' => $log->created_count ?? 0,
            'updated_count' => $log->updated_count ?? 0,
            'total_families' => ($log->created_count ?? 0) + ($log->updated_count ?? 0)
        ]);
    }

    /**
     * Handle the InsuranceImportLog "updated" event.
     */
    public function updated(InsuranceImportLog $log): void
    {
        // بررسی تغییر فیلد مهم
        if ($log->wasChanged('total_insurance_amount')) {
            $this->clearFinancialCache('updated', $log);
        }
    }

    /**
     * Handle the InsuranceImportLog "deleted" event.
     */
    public function deleted(InsuranceImportLog $log): void
    {
        $this->clearFinancialCache('deleted', $log);
    }

    /**
     * Handle the InsuranceImportLog "restored" event.
     */
    public function restored(InsuranceImportLog $log): void
    {
        $this->clearFinancialCache('restored', $log);
    }

    /**
     * پاک کردن کش مالی و لاگ کردن عملیات
     */
    private function clearFinancialCache(string $event, InsuranceImportLog $log): void
    {
        $this->cacheHelper->flush();

        Log::info('📥 لاگ ایمپورت بیمه ' . $event . ' - کش پاک شد', [
            'log_id' => $log->id,
            'file_name' => $log->file_name,
            'total_insurance_amount' => $log->total_insurance_amount,
            'created_count' => $log->created_count ?? 0,
            'updated_count' => $log->updated_count ?? 0,
            'event' => $event,
            'cache_keys_cleared' => count($this->cacheHelper->getAllKeys())
        ]);
    }
}