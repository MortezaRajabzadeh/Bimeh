<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Cache;

class ClearFinancialReportCache
{
    public function handle($event)
    {
        // پاک کردن کش هنگام تغییر در داده‌های مالی
        Cache::forget('financial_report_total_credit');
        Cache::forget('financial_report_total_debit');
        Cache::forget('funding_transactions_with_source');
        Cache::forget('family_allocations_with_relations');
        Cache::forget('insurance_allocations_with_family');
    }
}
