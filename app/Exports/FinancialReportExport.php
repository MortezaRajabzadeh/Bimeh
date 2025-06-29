<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\FundingTransaction;
use App\Models\InsuranceAllocation;
use App\Models\InsuranceImportLog;
use App\Models\InsurancePayment;
use App\Models\ShareAllocationLog;
use App\Models\Family;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * کلاس صدور گزارش مالی اکسل
 *
 * مطابق با اصول SOLID و استانداردهای PSR-4
 * امکان صدور گزارش کامل تراکنش‌های مالی بیمه
 */
class FinancialReportExport implements
    FromArray,
    WithHeadings,
    WithStyles,
    WithCustomStartCell,
    WithTitle,
    WithColumnWidths
{
    private array $filters;
    private Collection $transactions;
    private array $summary;

    /**
     * سازنده کلاس با Dependency Injection
     */
    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
        $this->prepareData();
    }

    /**
     * تهیه داده‌ها با بهینه‌سازی Eloquent
     */
    private function prepareData(): void
    {
        $this->transactions = $this->collectAllTransactions();
        $this->summary = $this->calculateSummary();
    }

    /**
     * جمع‌آوری تمام تراکنش‌ها با بهینه‌سازی کوئری
     */
    private function collectAllTransactions(): Collection
    {
        $allTransactions = collect();

        // 1. تراکنش‌های بودجه با eager loading
        $fundingTransactions = FundingTransaction::with('source')->get();
        foreach ($fundingTransactions as $trx) {
            $isAllocation = $trx->allocated ?? false;
            $title = $isAllocation ? 'تخصیص بودجه' : 'واریز بودجه';
            $type = $isAllocation ? 'debit' : 'credit';

            $allTransactions->push($this->formatTransaction([
                'id' => $trx->id,
                'title' => $title,
                'amount' => $trx->amount,
                'type' => $type,
                'date' => $trx->created_at,
                'description' => $trx->description ?? 'تراکنش مالی',
                'source' => $trx->source->name ?? 'نامشخص',
                'reference_no' => 'FUND-' . $trx->id,
            ]));
        }

        // 2. تخصیص‌های بودجه خانواده‌ها
        $familyAllocations = \App\Models\FamilyFundingAllocation::with(['family.members', 'fundingSource'])
            ->where('status', '!=', \App\Models\FamilyFundingAllocation::STATUS_PENDING)
            ->whereNull('transaction_id')
            ->get();

        foreach ($familyAllocations as $alloc) {
            $membersCount = $alloc->family ? $alloc->family->members->count() : 0;

            $allTransactions->push($this->formatTransaction([
                'id' => $alloc->id,
                'title' => 'تخصیص بودجه خانواده',
                'amount' => $alloc->amount,
                'type' => 'debit',
                'date' => $alloc->approved_at ?? $alloc->created_at,
                'description' => $alloc->description ?: 'تخصیص ' . $alloc->percentage . '% از حق بیمه',
                'source' => $alloc->fundingSource ? $alloc->fundingSource->name : 'منبع مالی نامشخص',
                'reference_no' => 'ALLOC-' . $alloc->id,
                'family_count' => 1,
                'members_count' => $membersCount,
            ]));
        }

        // 3. پرداخت‌های بیمه منفرد
        $insuranceAllocations = InsuranceAllocation::with(['family.members'])->get();
        foreach ($insuranceAllocations as $alloc) {
            $membersCount = $alloc->family ? $alloc->family->members->count() : 0;

            $allTransactions->push($this->formatTransaction([
                'id' => $alloc->id,
                'title' => 'پرداخت حق بیمه منفرد',
                'amount' => $alloc->amount,
                'type' => 'debit',
                'date' => $alloc->created_at,
                'description' => $alloc->description,
                'source' => 'پرداخت مستقیم',
                'reference_no' => 'INS-' . $alloc->id,
                'family_count' => 1,
                'members_count' => $membersCount,
            ]));
        }

        // 4. پرداخت‌های اکسل ایمپورت
        $importLogs = InsuranceImportLog::get();
        foreach ($importLogs as $log) {
            $allCodes = array_merge(
                is_array($log->created_family_codes) ? $log->created_family_codes : [],
                is_array($log->updated_family_codes) ? $log->updated_family_codes : []
            );
            $familyCount = count($allCodes);

            $membersCount = 0;
            if ($familyCount > 0) {
                $membersCount = Family::whereIn('family_code', $allCodes)
                    ->withCount('members')
                    ->get()
                    ->sum('members_count');
            }

            $allTransactions->push($this->formatTransaction([
                'id' => $log->id,
                'title' => 'ایمپورت حق بیمه از اکسل',
                'amount' => $log->total_insurance_amount,
                'type' => 'debit',
                'date' => $log->created_at,
                'description' => 'ایمپورت فایل: ' . ($log->file_name ?? 'نامشخص'),
                'source' => 'ایمپورت اکسل',
                'reference_no' => 'IMP-' . $log->id,
                'family_count' => $familyCount,
                'members_count' => $membersCount,
            ]));
        }

        // 5. پرداخت‌های سیستماتیک
        $insurancePayments = InsurancePayment::with(['familyInsurance.family', 'details.member'])->get();
        foreach ($insurancePayments as $payment) {
            $family = $payment->familyInsurance ? $payment->familyInsurance->family : null;
            $membersCount = $payment->insured_persons_count ?? ($family ? $family->members->count() : 0);

            $allTransactions->push($this->formatTransaction([
                'id' => $payment->id,
                'title' => 'پرداخت حق بیمه سیستماتیک',
                'amount' => $payment->total_amount,
                'type' => 'debit',
                'date' => $payment->payment_date ?? $payment->created_at,
                'description' => $payment->description,
                'source' => 'سیستم پرداخت',
                'reference_no' => $payment->transaction_reference,
                'family_count' => 1,
                'members_count' => $membersCount,
            ]));
        }

        // 6. تخصیص‌های سهم گروهی
        $allocationLogs = ShareAllocationLog::where('status', 'completed')
                                          ->where('total_amount', '>', 0)
                                          ->get();
        foreach ($allocationLogs as $log) {
            $allTransactions->push($this->formatTransaction([
                'id' => $log->id,
                'title' => 'تخصیص سهم گروهی',
                'amount' => $log->total_amount,
                'type' => 'debit',
                'date' => $log->updated_at,
                'description' => $log->description,
                'source' => 'تخصیص گروهی',
                'reference_no' => 'BATCH-' . $log->batch_id,
                'family_count' => $log->families_count,
                'members_count' => 0,
            ]));
        }

        // مرتب‌سازی بر اساس تاریخ (جدیدترین ابتدا)
        return $allTransactions->sortByDesc(function ($item) {
            return $item['date']->timestamp;
        })->values();
    }

    /**
     * فرمت‌بندی تراکنش مطابق با اصول SOLID
     */
    private function formatTransaction(array $data): array
    {
        return [
            'id' => $data['id'],
            'title' => $data['title'],
            'amount' => $data['amount'],
            'type' => $data['type'],
            'date' => $data['date'],
            'description' => $data['description'] ?? '',
            'source' => $data['source'] ?? '',
            'reference_no' => $data['reference_no'] ?? '',
            'family_count' => $data['family_count'] ?? 0,
            'members_count' => $data['members_count'] ?? 0,
        ];
    }

    /**
     * محاسبه خلاصه مالی
     */
    private function calculateSummary(): array
    {
        $totalCredit = $this->transactions
            ->where('type', 'credit')
            ->sum('amount');

        $totalDebit = $this->transactions
            ->where('type', 'debit')
            ->sum('amount');

        return [
            'total_credit' => $totalCredit,
            'total_debit' => $totalDebit,
            'balance' => $totalCredit - $totalDebit,
            'transactions_count' => $this->transactions->count(),
        ];
    }

    /**
     * ارائه داده‌ها به عنوان آرایه
     */
    public function array(): array
    {
        $data = [];

        // بخش خلاصه گزارش
        $data[] = ['خلاصه گزارش مالی بیمه'];
        $data[] = [];
        $data[] = ['کل واریزی:', number_format($this->summary['total_credit']) . ' ریال'];
        $data[] = ['کل پرداختی:', number_format($this->summary['total_debit']) . ' ریال'];
        $data[] = ['موجودی فعلی:', number_format($this->summary['balance']) . ' ریال'];
        $data[] = ['وضعیت مالی:', $this->summary['balance'] > 0 ? 'مثبت' : 'منفی'];
        $data[] = ['تعداد کل تراکنش‌ها:', $this->summary['transactions_count']];
        $data[] = ['تاریخ تولید گزارش:', jdate(now())->format('Y/m/d H:i')];
        $data[] = [];
        $data[] = [];

        // بخش جزئیات تراکنش‌ها
        foreach ($this->transactions as $transaction) {
            $data[] = [
                $transaction['id'],
                $transaction['title'],
                $transaction['description'],
                jdate($transaction['date'])->format('Y/m/d H:i'),
                $transaction['type'] === 'credit' ? 'واریز' : 'پرداخت',
                number_format($transaction['amount']),
                $transaction['source'],
                $transaction['reference_no'],
                $transaction['family_count'],
                $transaction['members_count'],
            ];
        }

        return $data;
    }

    /**
     * تعریف سرتیترهای جدول
     */
    public function headings(): array
    {
        return [
            'شناسه',
            'عنوان تراکنش',
            'توضیحات',
            'تاریخ و زمان',
            'نوع تراکنش',
            'مبلغ (ریال)',
            'منبع/مقصد',
            'شماره پیگیری',
            'تعداد خانواده',
            'تعداد اعضا',
        ];
    }

    /**
     * تنظیمات استایل صفحه اکسل
     */
    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();

        return [
            // استایل عنوان اصلی
            'A1' => [
                'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => '1565C0']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],

            // استایل بخش خلاصه
            'A3:B9' => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '2196F3'],
                    ],
                ],
            ],

            // استایل هدر جدول
            'A11:J11' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1976D2']
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],

            // استایل داده‌های جدول
            "A12:J{$highestRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ],

            // استایل ستون مبلغ (F)
            "F12:F{$highestRow}" => [
                'font' => ['bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ],
        ];
    }

    /**
     * سلول شروع برای هدرها
     */
    public function startCell(): string
    {
        return 'A11';
    }

    /**
     * عنوان برگه اکسل
     */
    public function title(): string
    {
        return 'گزارش مالی بیمه';
    }

    /**
     * عرض ستون‌ها
     */
    public function columnWidths(): array
    {
        return [
            'A' => 12,  // شناسه
            'B' => 30,  // عنوان تراکنش
            'C' => 35,  // توضیحات
            'D' => 20,  // تاریخ و زمان
            'E' => 15,  // نوع تراکنش
            'F' => 20,  // مبلغ
            'G' => 25,  // منبع/مقصد
            'H' => 20,  // شماره پیگیری
            'I' => 15,  // تعداد خانواده
            'J' => 15,  // تعداد اعضا
        ];
    }
}
