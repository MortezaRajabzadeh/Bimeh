<?php

namespace App\Observers;

use App\Models\FamilyFundingAllocation;
use App\Helpers\FinancialCacheHelper;
use Illuminate\Support\Facades\Log;

/**
 * Observer برای FamilyFundingAllocation
 * 
 * این Observer برای تخصیص‌های بودجه به خانواده‌ها است
 */
class FamilyFundingAllocationObserver
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
     * Handle the FamilyFundingAllocation "created" event.
     */
    public function created(FamilyFundingAllocation $allocation): void
    {
        $this->clearFinancialCache('created', $allocation);
    }

    /**
     * Handle the FamilyFundingAllocation "updated" event.
     */
    public function updated(FamilyFundingAllocation $allocation): void
    {
        $shouldClearCache = false;
        
        // فقط وقتی که allocation تایید شد یا مبلغ تغییر کرد
        if ($allocation->wasChanged('status') && $allocation->status !== FamilyFundingAllocation::STATUS_PENDING) {
            $shouldClearCache = true;
            
            Log::info('👨‍👩‍👧‍👦 تخصیص بودجه خانواده تایید شد', [
                'allocation_id' => $allocation->id,
                'family_id' => $allocation->family_id,
                'amount' => $allocation->amount,
                'old_status' => $allocation->getOriginal('status'),
                'new_status' => $allocation->status
            ]);
        }
        
        if ($allocation->wasChanged('amount') && $allocation->status !== FamilyFundingAllocation::STATUS_PENDING) {
            $shouldClearCache = true;
            
            Log::info('👨‍👩‍👧‍👦 مبلغ تخصیص بودجه خانواده تغییر کرد', [
                'allocation_id' => $allocation->id,
                'family_id' => $allocation->family_id,
                'old_amount' => $allocation->getOriginal('amount'),
                'new_amount' => $allocation->amount,
                'status' => $allocation->status
            ]);
        }
        
        if ($shouldClearCache) {
            $this->clearFinancialCache('updated', $allocation);
        }
    }

    /**
     * Handle the FamilyFundingAllocation "deleted" event.
     */
    public function deleted(FamilyFundingAllocation $allocation): void
    {
        $this->clearFinancialCache('deleted', $allocation);
    }

    /**
     * Handle the FamilyFundingAllocation "restored" event.
     */
    public function restored(FamilyFundingAllocation $allocation): void
    {
        $this->clearFinancialCache('restored', $allocation);
    }

    /**
     * پاک کردن کش مالی و لاگ کردن عملیات
     */
    private function clearFinancialCache(string $event, FamilyFundingAllocation $allocation): void
    {
        $this->cacheHelper->flush();

        Log::info('👨‍👩‍👧‍👦 تخصیص بودجه خانواده ' . $event . ' - کش پاک شد', [
            'allocation_id' => $allocation->id,
            'family_id' => $allocation->family_id,
            'amount' => $allocation->amount,
            'status' => $allocation->status,
            'percentage' => $allocation->percentage ?? null,
            'event' => $event,
            'cache_keys_cleared' => count($this->cacheHelper->getAllKeys())
        ]);
    }
}