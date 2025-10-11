<?php

namespace App\Observers;

use App\Models\FundingTransaction;
use App\Helpers\FinancialCacheHelper;
use Illuminate\Support\Facades\Log;

/**
 * Observer برای FundingTransaction
 * 
 * این Observer به صورت خودکار کش گزارش مالی را invalidate می‌کند
 * هنگامی که تراکنش‌های بودجه تغییر می‌کنند
 */
class FundingTransactionObserver
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
     * Handle the FundingTransaction "created" event.
     */
    public function created(FundingTransaction $transaction): void
    {
        $this->clearFinancialCache('created', $transaction);
    }

    /**
     * Handle the FundingTransaction "updated" event.
     */
    public function updated(FundingTransaction $transaction): void
    {
        // بررسی تغییر فیلدهای مهم
        $importantFields = ['amount', 'allocated', 'status'];
        $hasImportantChange = false;

        foreach ($importantFields as $field) {
            if ($transaction->wasChanged($field)) {
                $hasImportantChange = true;
                break;
            }
        }

        if ($hasImportantChange) {
            $this->clearFinancialCache('updated', $transaction);
            
            Log::info('💰 تراکنش بودجه به‌روزرسانی شد - فیلدهای تغییر یافته', [
                'transaction_id' => $transaction->id,
                'changed_fields' => array_keys($transaction->getChanges()),
                'old_amount' => $transaction->getOriginal('amount'),
                'new_amount' => $transaction->amount
            ]);
        }
    }

    /**
     * Handle the FundingTransaction "deleted" event.
     */
    public function deleted(FundingTransaction $transaction): void
    {
        $this->clearFinancialCache('deleted', $transaction);
    }

    /**
     * Handle the FundingTransaction "restored" event.
     */
    public function restored(FundingTransaction $transaction): void
    {
        $this->clearFinancialCache('restored', $transaction);
    }

    /**
     * پاک کردن کش مالی و لاگ کردن عملیات
     */
    private function clearFinancialCache(string $event, FundingTransaction $transaction): void
    {
        $this->cacheHelper->flush();

        Log::info('💰 تراکنش بودجه ' . $event . ' - کش پاک شد', [
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
            'allocated' => $transaction->allocated ?? false,
            'funding_source_id' => $transaction->funding_source_id,
            'event' => $event,
            'cache_keys_cleared' => count($this->cacheHelper->getAllKeys())
        ]);
    }
}