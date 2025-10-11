<?php

namespace App\Observers;

use App\Models\InsuranceAllocation;
use App\Helpers\FinancialCacheHelper;
use Illuminate\Support\Facades\Log;

/**
 * Observer برای InsuranceAllocation
 * 
 * این Observer مخصوص پرداخت‌های بیمه منفرد است
 */
class InsuranceAllocationObserver
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
     * Handle the InsuranceAllocation "created" event.
     */
    public function created(InsuranceAllocation $allocation): void
    {
        $this->clearFinancialCache('created', $allocation);
    }

    /**
     * Handle the InsuranceAllocation "updated" event.
     */
    public function updated(InsuranceAllocation $allocation): void
    {
        // بررسی تغییر فیلدهای مهم
        $importantFields = ['amount', 'paid_at', 'insurance_type'];
        $hasImportantChange = false;

        foreach ($importantFields as $field) {
            if ($allocation->wasChanged($field)) {
                $hasImportantChange = true;
                break;
            }
        }

        if ($hasImportantChange) {
            $this->clearFinancialCache('updated', $allocation);
        }
    }

    /**
     * Handle the InsuranceAllocation "deleted" event.
     */
    public function deleted(InsuranceAllocation $allocation): void
    {
        $this->clearFinancialCache('deleted', $allocation);
    }

    /**
     * Handle the InsuranceAllocation "restored" event.
     */
    public function restored(InsuranceAllocation $allocation): void
    {
        $this->clearFinancialCache('restored', $allocation);
    }

    /**
     * پاک کردن کش مالی و لاگ کردن عملیات
     */
    private function clearFinancialCache(string $event, InsuranceAllocation $allocation): void
    {
        $this->cacheHelper->flush();

        Log::info('🏥 تخصیص بیمه منفرد ' . $event . ' - کش پاک شد', [
            'allocation_id' => $allocation->id,
            'family_id' => $allocation->family_id,
            'amount' => $allocation->amount,
            'event' => $event,
            'cache_keys_cleared' => count($this->cacheHelper->getAllKeys())
        ]);
    }
}