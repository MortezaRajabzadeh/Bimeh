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

class FinancialReportController extends Controller
{
    /**
     * نمایش صفحه گزارش مالی
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        
        // محاسبه موجودی کل
        $totalCredit = FundingTransaction::sum('amount');
        $totalDebit = InsuranceAllocation::sum('amount') + InsuranceImportLog::sum('total_insurance_amount');
        $balance = $totalCredit - $totalDebit;

        // گرفتن همه تراکنش‌ها - ساده و مستقیم
        $allTransactions = collect();

        // 1. تراکنش‌های بودجه
        $fundingTransactions = FundingTransaction::with('source')->get();
        foreach ($fundingTransactions as $trx) {
            $allTransactions->push([
                'title' => 'تخصیص بودجه',
                'amount' => $trx->amount,
                'type' => 'credit',
                'date' => $trx->created_at,
                'date_formatted' => jdate($trx->created_at)->format('Y/m/d'),
                'sort_timestamp' => $trx->created_at->timestamp,
                'description' => $trx->description,
                'reference_no' => $trx->reference_no,
                'details' => $trx->source ? $trx->source->name : null,
                'created_family_codes' => [],
                'updated_family_codes' => [],
                'members' => collect(),
                'family' => null,
            ]);
        }

        // 2. پرداخت‌های بیمه
        $insuranceAllocations = InsuranceAllocation::with(['family.members'])->get();
        foreach ($insuranceAllocations as $alloc) {
            $allTransactions->push([
                'title' => 'حق بیمه پرداختی',
                'amount' => $alloc->amount,
                'type' => 'debit',
                'date' => $alloc->created_at,
                'date_formatted' => jdate($alloc->created_at)->format('Y/m/d'),
                'sort_timestamp' => $alloc->created_at->timestamp,
                'description' => $alloc->description,
                'reference_no' => null,
                'details' => null,
                'family' => $alloc->family,
                'members' => $alloc->family ? $alloc->family->members : collect(),
                'created_family_codes' => [],
                'updated_family_codes' => [],
            ]);
        }

        // 3. ایمپورت‌های اکسل
        $importLogs = InsuranceImportLog::get();
        foreach ($importLogs as $log) {
            $allTransactions->push([
                'title' => 'بیمه پرداختی (ایمپورت اکسل)',
                'amount' => $log->total_insurance_amount,
                'type' => 'debit',
                'date' => $log->created_at,
                'date_formatted' => jdate($log->created_at)->format('Y/m/d'),
                'sort_timestamp' => $log->created_at->timestamp,
                'description' => 'ایمپورت اکسل: ' . ($log->file_name ?? ''),
                'reference_no' => null,
                'details' => null,
                'count_success' => $log->created_count + $log->updated_count,
                'members' => collect(),
                'family' => null,
                'updated_family_codes' => is_array($log->updated_family_codes) ? $log->updated_family_codes : [],
                'created_family_codes' => is_array($log->created_family_codes) ? $log->created_family_codes : [],
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

        return view('insurance.financial-report', compact('transactionsPaginated', 'balance', 'logs', 'totalAmount'));
    }

    public function importLogs()
    {
        $logs = InsuranceImportLog::with('user')->orderByDesc('created_at')->paginate(20);
        $totalAmount = InsuranceImportLog::sum('total_insurance_amount');
        return view('insurance.financial-report', compact('logs', 'totalAmount'));
    }
} 