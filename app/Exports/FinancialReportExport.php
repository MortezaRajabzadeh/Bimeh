<?php

namespace App\Exports;

use App\Models\FundingTransaction;
use App\Models\InsuranceAllocation;
use App\Models\InsuranceImportLog;
use App\Models\InsurancePayment;
use App\Models\Family;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinancialReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    private $filters;

    public function __construct(array $filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $allTransactions = collect();

        // 1. تراکنش‌های بودجه
        $fundingTransactions = FundingTransaction::with('source')->get();
        foreach ($fundingTransactions as $trx) {
            $allTransactions->push([
                'type' => 'budget_allocation',
                'title' => __('financial.transaction_types.budget_allocation'),
                'amount' => $trx->amount,
                'transaction_type' => 'credit',
                'date' => $trx->created_at,
                'description' => $trx->description,
                'reference_no' => $trx->reference_no,
                'family_count' => 0,
                'members_count' => 0,
                'details' => $trx->source ? $trx->source->name : null,
            ]);
        }

        // 2. پرداخت‌های بیمه منفرد
        $insuranceAllocations = InsuranceAllocation::with(['family.members'])->get();
        foreach ($insuranceAllocations as $alloc) {
            $membersCount = $alloc->family ? $alloc->family->members->count() : 0;
            
            $allTransactions->push([
                'type' => 'premium_payment',
                'title' => __('financial.transaction_types.premium_payment'),
                'amount' => $alloc->amount,
                'transaction_type' => 'debit',
                'date' => $alloc->created_at,
                'description' => $alloc->description,
                'reference_no' => null,
                'family_count' => 1,
                'members_count' => $membersCount,
                'details' => $alloc->family ? $alloc->family->family_code : null,
            ]);
        }

        // 3. پرداخت‌های ایمپورت اکسل
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

            $allTransactions->push([
                'type' => 'premium_import',
                'title' => __('financial.transaction_types.premium_import'),
                'amount' => $log->total_insurance_amount,
                'transaction_type' => 'debit',
                'date' => $log->created_at,
                'description' => 'ایمپورت اکسل: ' . ($log->file_name ?? ''),
                'reference_no' => null,
                'family_count' => $familyCount,
                'members_count' => $membersCount,
                'details' => implode(', ', array_slice($allCodes, 0, 5)) . ($familyCount > 5 ? '...' : ''),
            ]);
        }

        // 4. پرداخت‌های سیستماتیک
        $insurancePayments = InsurancePayment::with(['familyInsurance.family'])->get();
        foreach ($insurancePayments as $payment) {
            $family = $payment->familyInsurance ? $payment->familyInsurance->family : null;
            $membersCount = $payment->insured_persons_count ?? ($family ? $family->members->count() : 0);
            
            $allTransactions->push([
                'type' => 'premium_payment_systematic',
                'title' => __('financial.transaction_types.premium_payment'),
                'amount' => $payment->total_amount,
                'transaction_type' => 'debit',
                'date' => $payment->payment_date ?? $payment->created_at,
                'description' => $payment->description,
                'reference_no' => $payment->transaction_reference,
                'family_count' => 1,
                'members_count' => $membersCount,
                'details' => $family ? $family->family_code : null,
            ]);
        }

        return $allTransactions->sortByDesc(function ($item) {
            return $item['date']->timestamp;
        });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'نوع تراکنش',
            'شرح',
            'مبلغ (ریال)',
            'نوع عملیات',
            'تاریخ',
            'توضیحات',
            'شماره مرجع',
            'تعداد خانواده',
            'تعداد اعضا',
            'جزئیات',
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        return [
            $row['title'],
            $row['title'],
            number_format($row['amount']),
            $row['transaction_type'] === 'credit' ? 'واریز' : 'برداشت',
            jdate($row['date'])->format('Y/m/d H:i'),
            $row['description'],
            $row['reference_no'],
            $row['family_count'],
            $row['members_count'],
            $row['details'],
        ];
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Header row
            1 => ['font' => ['bold' => true]],
        ];
    }
} 