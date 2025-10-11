<?php

namespace App\Observers;

use App\Models\InsuranceShare;
use App\Helpers\FinancialCacheHelper;
use Illuminate\Support\Facades\Log;

/**
 * Observer برای InsuranceShare
 * 
 * این Observer برای سهم‌های فردی بیمه است
 */
class InsuranceShareObserver
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
     * Handle the InsuranceShare "created" event.
     */
    public function created(InsuranceShare $share): void
    {
        $this->clearFinancialCache('created', $share);
    }

    /**
     * Handle the InsuranceShare "updated" event.
     */
    public function updated(InsuranceShare $share): void
    {
        // بررسی تغییر فیلدهای مهم و فقط سهم‌های نهایی شده
        $importantFields = ['amount', 'percentage'];
        $hasImportantChange = false;

        foreach ($importantFields as $field) {
            if ($share->wasChanged($field)) {
                $hasImportantChange = true;
                break;
            }
        }

        // فقط اگر مبلغ بیشتر از 0 باشد (سهم‌های draft نادیده گرفته می‌شوند)
        if ($hasImportantChange && $share->amount > 0) {
            $this->clearFinancialCache('updated', $share);
        }
    }

    /**
     * Handle the InsuranceShare "deleted" event.
     */
    public function deleted(InsuranceShare $share): void
    {
        $this->clearFinancialCache('deleted', $share);
    }

    /**
     * Handle the InsuranceShare "restored" event.
     */
    public function restored(InsuranceShare $share): void
    {
        $this->clearFinancialCache('restored', $share);
    }

    /**
     * پاک کردن کش مالی و لاگ کردن عملیات
     */
    private function clearFinancialCache(string $event, InsuranceShare $share): void
    {
        $this->cacheHelper->flush();

        Log::info('📊 سهم بیمه ' . $event . ' - کش پاک شد', [
            'share_id' => $share->id,
            'family_insurance_id' => $share->family_insurance_id,
            'amount' => $share->amount,
            'percentage' => $share->percentage,
            'is_manual' => $share->isManual(),
            'import_log_id' => $share->import_log_id,
            'event' => $event,
            'cache_keys_cleared' => count($this->cacheHelper->getAllKeys())
        ]);
    }
}