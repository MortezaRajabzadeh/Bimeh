<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\FundingTransaction;
use App\Models\InsuranceAllocation;
use App\Models\Family;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use App\Models\InsuranceImportLog;
use App\Models\InsuranceShare;
use App\Models\InsurancePayment;
use App\Models\ShareAllocationLog; // مدل جدید اضافه شده
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FinancialReportExport;
use Illuminate\Support\Facades\Cache;

class FinancialReportController extends Controller
{
    /**
     * نمایش صفحه گزارش مالی
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);

        // محاسبه موجودی کل با کش (مدت زمان کش: 10 دقیقه)
        $totalCredit = Cache::remember('financial_report_total_credit', 600, function () {
            return FundingTransaction::sum('amount');
        });

        $totalDebit = Cache::remember('financial_report_total_debit', 600, function () {
            return InsuranceAllocation::sum('amount') +
                   InsuranceImportLog::sum('total_insurance_amount') +
                   InsurancePayment::sum('total_amount') +
                   ShareAllocationLog::where('status', 'completed')->sum('total_amount');
        });

        $balance = $totalCredit - $totalDebit;

        // گرفتن همه تراکنش‌ها با جزئیات بهتر
        $allTransactions = collect();

        // 1. تراکنش‌های بودجه
        $fundingTransactions = FundingTransaction::with('source')->get();
        foreach ($fundingTransactions as $trx) {
            $isAllocation = $trx->allocated ?? false;
            $title = $isAllocation ? 'تخصیص بودجه' : __('financial.transaction_types.budget_allocation');
            $type = $isAllocation ? 'debit' : 'credit';

            $allTransactions->push([
                'id' => $trx->id,
                'title' => $title,
                'amount' => $trx->amount,
                'type' => $type,
                'date' => $trx->created_at,
                'date_formatted' => jdate($trx->created_at)->format('Y/m/d'),
                'sort_timestamp' => $trx->created_at->timestamp,
                'description' => $trx->description ?? 'تراکنش مالی',
                'source' => $trx->source->name ?? 'نامشخص',
            ]);
        }

        // 1.5. تخصیص‌های بودجه خانواده‌ها
        $familyAllocations = \App\Models\FamilyFundingAllocation::with(['family.members', 'fundingSource', 'transaction'])
            ->where('status', '!=', \App\Models\FamilyFundingAllocation::STATUS_PENDING)
            ->get();

        foreach ($familyAllocations as $alloc) {
            // فقط تخصیص‌هایی که به تراکنش مالی متصل نیستند را اضافه می‌کنیم
            // تا از دوبار شمارش جلوگیری شود
            if ($alloc->transaction_id === null) {
                $membersCount = $alloc->family ? $alloc->family->members->count() : 0;

                $allTransactions->push([
                    'id' => $alloc->id,
                    'title' => 'تخصیص بودجه خانواده',
                    'amount' => $alloc->amount,
                    'type' => 'debit',
                    'date' => $alloc->approved_at ?? $alloc->created_at,
                    'date_formatted' => jdate($alloc->approved_at ?? $alloc->created_at)->format('Y/m/d'),
                    'sort_timestamp' => ($alloc->approved_at ?? $alloc->created_at)->timestamp,
                    'description' => $alloc->description ?: 'تخصیص ' . $alloc->percentage . '% از حق بیمه',
                    'reference_no' => 'ALLOC-' . $alloc->id,
                    'details' => $alloc->fundingSource ? $alloc->fundingSource->name : 'منبع مالی نامشخص',
                    'payment_id' => $alloc->id,
                    'family_count' => 1,
                    'members_count' => $membersCount,
                    'family' => $alloc->family,
                    'members' => $alloc->family ? $alloc->family->members : collect(),
                    'created_family_codes' => [],
                    'updated_family_codes' => [],
                    'allocation_type' => 'family_funding'
                ]);
            }
        }

        // 2. پرداخت‌های بیمه منفرد (InsuranceAllocation)
        $insuranceAllocations = InsuranceAllocation::with(['family.members'])->get();
        foreach ($insuranceAllocations as $alloc) {
            $membersCount = $alloc->family ? $alloc->family->members->count() : 0;

            $allTransactions->push([
                'id' => $alloc->id,
                'title' => __('financial.transaction_types.premium_payment'),
                'amount' => $alloc->amount,
                'type' => 'debit',
                'date' => $alloc->created_at,
                'date_formatted' => jdate($alloc->created_at)->format('Y/m/d'),
                'sort_timestamp' => $alloc->created_at->timestamp,
                'description' => $alloc->description,
                'reference_no' => null,
                'details' => null,
                'payment_id' => $alloc->id,
                'family_count' => 1,
                'members_count' => $membersCount,
                'family' => $alloc->family,
                'members' => $alloc->family ? $alloc->family->members : collect(),
                'created_family_codes' => [],
                'updated_family_codes' => [],
            ]);
        }

        // 3. پرداخت‌های اکسل ایمپورت شده
        $importLogs = InsuranceImportLog::get();
        foreach ($importLogs as $log) {
            $allCodes = array_merge(
                is_array($log->created_family_codes) ? $log->created_family_codes : [],
                is_array($log->updated_family_codes) ? $log->updated_family_codes : []
            );
            $familyCount = count($allCodes);

            // محاسبه تعداد اعضا
            $membersCount = 0;
            if ($familyCount > 0) {
                $membersCount = Family::whereIn('family_code', $allCodes)
                    ->withCount('members')
                    ->get()
                    ->sum('members_count');
            }

            $allTransactions->push([
                'id' => $log->id,
                'title' => __('financial.transaction_types.premium_import'),
                'amount' => $log->total_insurance_amount,
                'type' => 'debit',
                'date' => $log->created_at,
                'date_formatted' => jdate($log->created_at)->format('Y/m/d'),
                'sort_timestamp' => $log->created_at->timestamp,
                'description' => 'ایمپورت اکسل: ' . ($log->file_name ?? ''),
                'reference_no' => null,
                'details' => null,
                'payment_id' => null,
                'family_count' => $familyCount,
                'members_count' => $membersCount,
                'count_success' => $log->created_count + $log->updated_count,
                'members' => collect(),
                'family' => null,
                'updated_family_codes' => is_array($log->updated_family_codes) ? $log->updated_family_codes : [],
                'created_family_codes' => is_array($log->created_family_codes) ? $log->created_family_codes : [],
            ]);
        }

        // 4. پرداخت‌های سیستماتیک (InsurancePayment)
        $insurancePayments = InsurancePayment::with(['familyInsurance.family', 'details.member'])->get();
        foreach ($insurancePayments as $payment) {
            $family = $payment->familyInsurance ? $payment->familyInsurance->family : null;
            $membersCount = $payment->insured_persons_count ?? ($family ? $family->members->count() : 0);

            $allTransactions->push([
                'id' => $payment->id,
                'title' => __('financial.transaction_types.premium_payment'),
                'amount' => $payment->total_amount,
                'type' => 'debit',
                'date' => $payment->payment_date ?? $payment->created_at,
                'date_formatted' => jdate($payment->payment_date ?? $payment->created_at)->format('Y/m/d'),
                'sort_timestamp' => ($payment->payment_date ?? $payment->created_at)->timestamp,
                'description' => $payment->description,
                'reference_no' => $payment->transaction_reference,
                'details' => null,
                'payment_id' => $payment->id,
                'family_count' => 1,
                'members_count' => $membersCount,
                'family' => $family,
                'members' => $payment->details ? $payment->details->map->member : collect(),
                'created_family_codes' => [],
                'updated_family_codes' => [],
            ]);
        }

        // 5. سهم‌های بیمه (InsuranceShare)
        $insuranceShares = InsuranceShare::with(['familyInsurance.family.members', 'fundingSource'])
            ->whereHas('familyInsurance', function($query) {
                $query->where('status', 'insured');
            })
            ->where('amount', '>', 0) // فقط سهم‌هایی که مبلغ نهایی دارند
            ->get();

        foreach ($insuranceShares as $share) {
            $family = $share->familyInsurance->family;
            $membersCount = $family ? $family->members->count() : 0;

            // هر سهم را به عنوان یک تراکنش بدهی جداگانه در نظر می‌گیریم
            $allTransactions->push([
                'id' => 'share-' . $share->id, // یک شناسه منحصر به فرد
                'title' => 'پرداخت سهم بیمه',
                'amount' => $share->amount, // مبلغ نهایی سهم
                'type' => 'debit',
                'date' => $share->updated_at, // تاریخ نهایی شدن مبلغ
                'date_formatted' => jdate($share->updated_at)->format('Y/m/d'),
                'sort_timestamp' => $share->updated_at->timestamp,
                'description' => "پرداخت سهم {$share->percentage}% برای خانواده " . ($family->name ?? $family->family_code),
                'reference_no' => 'SHARE-' . $share->id,
                'details' => $share->fundingSource ? $share->fundingSource->name : 'منبع مالی نامشخص',
                'payment_id' => $share->id,
                'family_count' => 1,
                'members_count' => $membersCount,
                'family' => $family,
                'members' => $family ? $family->members : collect(),
                'created_family_codes' => [],
                'updated_family_codes' => [],
                'is_share' => true // برای تشخیص در view
            ]);
        }

        // 5. خواندن لاگ‌های تخصیص سهم گروهی (جایگزین سهم‌های تکی)
        $allocationLogs = ShareAllocationLog::where('status', 'completed')
                                          ->where('total_amount', '>', 0)
                                          ->get();
        foreach ($allocationLogs as $log) {
            $allTransactions->push([
                'id' => 'alloc-' . $log->id,
                'title' => 'تخصیص سهم گروهی',
                'amount' => $log->total_amount,
                'type' => 'debit',
                'date' => $log->updated_at,
                'date_formatted' => jdate($log->updated_at)->format('Y/m/d'),
                'sort_timestamp' => $log->updated_at->timestamp,
                'description' => $log->description,
                'details' => $log->families_count . ' خانواده',
                'payment_id' => $log->id,
                'family_count' => $log->families_count,
                'batch_id' => $log->batch_id,
            ]);
        }

        // ساده‌ترین sorting - فقط بر اساس timestamp
        $sortedTransactions = $allTransactions->sortByDesc('sort_timestamp')->values();

        // Manual pagination
        $currentPage = $request->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $paginatedTransactions = $sortedTransactions->slice($offset, $perPage);

        $transactionsPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedTransactions,
            $sortedTransactions->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );

        // گزارش ایمپورت‌های اکسل
        $logs = InsuranceImportLog::with('user')->orderByDesc('created_at')->paginate(20, ['*'], 'logs_page');
        $totalAmount = InsuranceImportLog::sum('total_insurance_amount');

        // اطلاعات خانواده‌های بیمه شده برای نمایش در گزارش
        $insuredFamilies = \App\Models\Family::whereHas('insurances', function($query) {
            $query->where('status', 'active');
        })->with(['insurances' => function($query) {
            $query->where('status', 'active')->with('shares');
        }, 'members'])
        ->paginate(10, ['*'], 'families_page');

        return view('insurance.financial-report', compact('transactionsPaginated', 'balance', 'logs', 'totalAmount', 'insuredFamilies'));
    }

    /**
     * صدور گزارش اکسل
     */
    public function exportExcel(Request $request)
    {
        try {
            // اعتبارسنجی ورودی‌ها
            $validated = $request->validate([
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'type' => 'nullable|in:credit,debit,all',
                'format' => 'nullable|in:xlsx,csv'
            ]);

            // بررسی دسترسی کاربر
            if (!auth()->user()->can('view advanced reports')) {
                return back()->with('error', 'شما دسترسی لازم برای دانلود گزارش را ندارید.');
            }

            // بررسی وجود کلاس Export
            if (!class_exists(\App\Exports\FinancialReportExport::class)) {
                return back()->with('error', 'کلاس صدور گزارش یافت نشد. لطفاً با مدیر سیستم تماس بگیرید.');
            }

            // تولید نام فایل با تاریخ شمسی
            $persianDate = jdate(now())->format('Y-m-d_H-i-s');
            $format = $validated['format'] ?? 'xlsx';
            $fileName = "financial_report_{$persianDate}.{$format}";

            // ثبت لاگ شروع عملیات


            // تولید گزارش
            $export = new \App\Exports\FinancialReportExport($validated);

            // انتخاب نوع فایل
            $excelType = $format === 'csv' ?
                \Maatwebsite\Excel\Excel::CSV :
                \Maatwebsite\Excel\Excel::XLSX;

            // دانلود فایل
            return Excel::download($export, $fileName, $excelType, [
                'Content-Type' => $format === 'csv' ? 'text/csv' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->validator)
                ->with('error', 'داده‌های ورودی نامعتبر است.');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->with('error', 'شما دسترسی لازم برای این عملیات را ندارید.');

        } catch (\Maatwebsite\Excel\Exceptions\LaravelExcelException $e) {


            return back()->with('error', 'خطا در تولید فایل اکسل: ' . $e->getMessage());

        } catch (\Exception $e) {
            // ثبت خطای عمومی در لاگ


            // نمایش پیام خطا به کاربر
            return back()->with('error', 'خطا در تولید گزارش: ' . $e->getMessage());
        }
    }
    /**
     * نمایش جزئیات پرداخت
     */
    public function paymentDetails(Request $request, $paymentId)
    {
        $type = $request->get('type', 'allocation'); // allocation, payment, import, family_funding

        switch ($type) {
            case 'allocation':
                $payment = InsuranceAllocation::with(['family.members'])->findOrFail($paymentId);
                $families = collect([$payment->family]);
                break;

            case 'payment':
                $payment = InsurancePayment::with(['familyInsurance.family', 'details.member'])->findOrFail($paymentId);
                $families = collect([$payment->familyInsurance->family]);
                break;

            case 'import':
                $importLog = InsuranceImportLog::findOrFail($paymentId);
                $allCodes = array_merge(
                    is_array($importLog->created_family_codes) ? $importLog->created_family_codes : [],
                    is_array($importLog->updated_family_codes) ? $importLog->updated_family_codes : []
                );
                $families = Family::whereIn('family_code', $allCodes)->with('members')->get();
                break;

            case 'family_funding':
                $allocation = \App\Models\FamilyFundingAllocation::with(['family.members', 'fundingSource'])->findOrFail($paymentId);
                $families = collect([$allocation->family]);
                $payment = $allocation;
                break;

            default:
                abort(404);
        }

        return view('insurance.payment-details', compact('families', 'type', 'paymentId', 'payment'));
    }

    public function importLogs()
    {
        $logs = InsuranceImportLog::with('user')->orderByDesc('created_at')->paginate(20);
        $totalAmount = InsuranceImportLog::sum('total_insurance_amount');
        return view('insurance.financial-report', compact('logs', 'totalAmount'));
    }

    /**
     * نمایش جزئیات تخصیص بیمه
     */
    public function shareDetails($shareId)
    {
        $share = \App\Models\InsuranceShare::with(['family.members', 'fundingSource', 'creator'])
            ->findOrFail($shareId);

        $family = $share->family;
        $shareService = new \App\Services\InsuranceShareService();
        $shareSummary = $shareService->getSummary($family->id);

        return view('insurance.share-details', [
            'share' => $share,
            'family' => $family,
            'shareSummary' => $shareSummary
        ]);
    }

    /**
     * پاک کردن کش گزارش مالی
     */
    public function clearCache()
    {
        Cache::forget('financial_report_total_credit');
        Cache::forget('financial_report_total_debit');
        Cache::forget('funding_transactions_with_source');
        Cache::forget('family_allocations_with_relations');
        Cache::forget('insurance_allocations_with_family');

        return back()->with('success', 'کش گزارش مالی پاک شد.');
    }
}
