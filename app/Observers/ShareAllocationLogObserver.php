<?php

namespace App\Observers;

use App\Models\ShareAllocationLog;
use App\Helpers\FinancialCacheHelper;
use Illuminate\Support\Facades\Log;

/**
 * Observer برای ShareAllocationLog
 * 
 * این Observer برای bulk allocation است و باید دقت کرد
 * که فقط در زمان مناسب کش را پاک کند
 */
class ShareAllocationLogObserver
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
     * Handle the ShareAllocationLog "created" event.
     */
    public function created(ShareAllocationLog $log): void
    {
        $this->clearFinancialCache('created', $log);
    }

    /**
     * Handle the ShareAllocationLog "updated" event.
     */
    public function updated(ShareAllocationLog $log): void
    {
        $shouldClearCache = false;

        // فقط وقتی که allocation کامل شد، کش را پاک کن
        if ($log->wasChanged('status') && $log->status === 'completed') {
            $shouldClearCache = true;
            
            Log::info('📦 تخصیص سهم گروهی کامل شد', [
                'log_id' => $log->id,
                'batch_id' => $log->batch_id,
                'families_count' => $log->families_count,
                'total_amount' => $log->total_amount,
                'old_status' => $log->getOriginal('status'),
                'new_status' => $log->status
            ]);
        }
        
        // یا اگر مبلغ تغییر کرد و status قبلاً completed بود
        if ($log->wasChanged('total_amount') && $log->status === 'completed') {
            $shouldClearCache = true;
            
            Log::info('📦 مبلغ تخصیص سهم گروهی تغییر کرد', [
                'log_id' => $log->id,
                'batch_id' => $log->batch_id,
                'old_amount' => $log->getOriginal('total_amount'),
                'new_amount' => $log->total_amount
            ]);
        }

        if ($shouldClearCache) {
            $this->clearFinancialCache('updated', $log);
        }
    }

    /**
     * Handle the ShareAllocationLog "deleted" event.
     */
    public function deleted(ShareAllocationLog $log): void
    {
        $this->clearFinancialCache('deleted', $log);
    }

    /**
     * Handle the ShareAllocationLog "restored" event.
     */
    public function restored(ShareAllocationLog $log): void
    {
        $this->clearFinancialCache('restored', $log);
    }

    /**
     * پاک کردن کش مالی و لاگ کردن عملیات
     */
    private function clearFinancialCache(string $event, ShareAllocationLog $log): void
    {
        $this->cacheHelper->flush();

        Log::info('📦 لاگ تخصیص سهم گروهی ' . $event . ' - کش پاک شد', [
            'log_id' => $log->id,
            'batch_id' => $log->batch_id,
            'families_count' => $log->families_count,
            'total_amount' => $log->total_amount,
            'status' => $log->status,
            'event' => $event,
            'cache_keys_cleared' => count($this->cacheHelper->getAllKeys())
        ]);
    }
}