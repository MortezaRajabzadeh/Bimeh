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
        // دریافت تراکنش‌های بودجه (credit)
        $fundingTransactions = FundingTransaction::with('source')->orderBy('created_at')->get();
        // دریافت پرداخت‌های بیمه (debit)
        $insuranceAllocations = InsuranceAllocation::with(['family.members'])->orderBy('created_at')->get();

        $transactions = [];
        $balance = 0;

        // تراکنش‌های بودجه (credit)
        foreach ($fundingTransactions as $trx) {
            $transactions[] = [
                'title' => 'تخصیص بودجه',
                'amount' => $trx->amount,
                'type' => 'credit',
                'date' => jdate($trx->created_at)->format('Y/m/d'),
                'description' => $trx->description,
                'reference_no' => $trx->reference_no,
                'details' => $trx->source ? $trx->source->name : null,
            ];
            $balance += $trx->amount;
        }

        // پرداخت‌های بیمه (debit)
        foreach ($insuranceAllocations as $alloc) {
            $family = $alloc->family;
            $members = $family ? $family->members : collect();
            $transactions[] = [
                'title' => 'حق بیمه پرداختی',
                'amount' => $alloc->amount,
                'type' => 'debit',
                'date' => jdate($alloc->created_at)->format('Y/m/d'),
                'description' => $alloc->description,
                'family' => $family,
                'members' => $members,
            ];
            $balance -= $alloc->amount;
        }

        // مرتب‌سازی بر اساس تاریخ (جدیدترین بالا)
        usort($transactions, function($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });

        // اضافه کردن گزارش ایمپورت‌ها به صورت هر لاگ یک تراکنش جداگانه
        $logs = InsuranceImportLog::with('user')->orderByDesc('created_at')->paginate(20);
        $totalAmount = InsuranceImportLog::sum('total_insurance_amount');

        // تراکنش‌های ایمپورت اکسل (هر لاگ یک تراکنش جدا)
        foreach (InsuranceImportLog::orderByDesc('created_at')->get() as $log) {
            // فقط خانواده‌هایی که موفق یا بروزرسانی شده‌اند
            $familyCodes = is_array($log->family_codes) ? $log->family_codes : [];
            $created = is_array($log->created_family_codes ?? null) ? $log->created_family_codes : [];
            $updated = is_array($log->updated_family_codes ?? null) ? $log->updated_family_codes : [];
            $validCodes = array_unique(array_merge($created, $updated));
            // اگر فیلدهای جدا نداری، فقط همه family_codes را نگه دار
            if (count($validCodes)) {
                $familyCodes = array_values(array_intersect($familyCodes, $validCodes));
            }
            $transactions[] = [
                'title' => 'بیمه پرداختی (ایمپورت اکسل)',
                'amount' => $log->total_insurance_amount,
                'type' => 'debit',
                'date' => jdate($log->created_at)->format('Y/m/d'),
                'description' => 'ایمپورت اکسل: ' . ($log->file_name ?? ''),
                'count_success' => $log->created_count + $log->updated_count,
                'members' => collect(),
                'updated_family_codes' => is_array($log->updated_family_codes ?? null) ? $log->updated_family_codes : [],
                'created_family_codes' => is_array($log->created_family_codes ?? null) ? $log->created_family_codes : [],
            ];
            $balance -= $log->total_insurance_amount;
        }

        return view('insurance.financial-report', compact('transactions', 'balance', 'logs', 'totalAmount'));
    }

    public function importLogs()
    {
        $logs = InsuranceImportLog::with('user')->orderByDesc('created_at')->paginate(20);
        $totalAmount = InsuranceImportLog::sum('total_insurance_amount');
        return view('insurance.financial-report', compact('logs', 'totalAmount'));
    }
} 