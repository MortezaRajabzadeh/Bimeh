<?php

namespace App\Observers;

use App\Models\InsurancePayment;
use App\Helpers\FinancialCacheHelper;
use Illuminate\Support\Facades\Log;

/**
 * Observer برای InsurancePayment
 * 
 * این Observer برای پرداخت‌های بیمه سیستماتیک است
 */
class InsurancePaymentObserver
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
     * Handle the InsurancePayment "created" event.
     */
    public function created(InsurancePayment $payment): void
    {
        $this->clearFinancialCache('created', $payment);
    }

    /**
     * Handle the InsurancePayment "updated" event.
     */
    public function updated(InsurancePayment $payment): void
    {
        $shouldClearCache = false;

        // بررسی تغییر فیلدهای مهم
        $importantFields = ['total_amount', 'payment_status', 'payment_date'];
        
        foreach ($importantFields as $field) {
            if ($payment->wasChanged($field)) {
                $shouldClearCache = true;
                break;
            }
        }

        // optimization: فقط وقتی payment_status به 'paid' تغییر کرده باشد
        if ($payment->wasChanged('payment_status') && $payment->payment_status === 'paid') {
            $shouldClearCache = true;
            
            Log::info('💳 پرداخت بیمه نهایی شد', [
                'payment_id' => $payment->id,
                'payment_code' => $payment->payment_code ?? null,
                'total_amount' => $payment->total_amount,
                'old_status' => $payment->getOriginal('payment_status'),
                'new_status' => $payment->payment_status
            ]);
        }

        if ($shouldClearCache) {
            $this->clearFinancialCache('updated', $payment);
        }
    }

    /**
     * Handle the InsurancePayment "deleted" event.
     */
    public function deleted(InsurancePayment $payment): void
    {
        $this->clearFinancialCache('deleted', $payment);
    }

    /**
     * Handle the InsurancePayment "restored" event.
     */
    public function restored(InsurancePayment $payment): void
    {
        $this->clearFinancialCache('restored', $payment);
    }

    /**
     * پاک کردن کش مالی و لاگ کردن عملیات
     */
    private function clearFinancialCache(string $event, InsurancePayment $payment): void
    {
        $this->cacheHelper->flush();

        Log::info('💳 پرداخت بیمه سیستماتیک ' . $event . ' - کش پاک شد', [
            'payment_id' => $payment->id,
            'payment_code' => $payment->payment_code ?? null,
            'total_amount' => $payment->total_amount,
            'payment_status' => $payment->payment_status ?? null,
            'event' => $event,
            'cache_keys_cleared' => count($this->cacheHelper->getAllKeys())
        ]);
    }
}