<?php

namespace App\Listeners;

use App\Helpers\FinancialCacheHelper;
use Illuminate\Support\Facades\Log;

/**
 * Listener برای پاک کردن کش گزارش مالی
 * 
 * این Listener به صورت خودکار کش گزارش مالی را پاک می‌کند
 * هنگامی که event‌های مالی رخ می‌دهند.
 * 
 * نکته: با Observer‌ها، دیگر نیازی به این Listener نیست
 * اما برای backward compatibility نگه داشته شده است
 */
class ClearFinancialReportCache
{
    protected FinancialCacheHelper $cacheHelper;

    public function __construct(FinancialCacheHelper $cacheHelper = null)
    {
        $this->cacheHelper = $cacheHelper ?? new FinancialCacheHelper();
    }

    /**
     * Handle the event.
     * 
     * این Listener به صورت خودکار کش گزارش مالی را پاک می‌کند
     * هنگامی که event‌های مالی رخ می‌دهند.
     * 
     * @param mixed $event
     * @return void
     */
    public function handle($event)
    {
        $this->cacheHelper->flush();
        
        Log::info('🔄 کش گزارش مالی توسط Listener پاک شد', [
            'event' => get_class($event),
            'keys_cleared' => count($this->cacheHelper->getAllKeys())
        ]);
    }
}
