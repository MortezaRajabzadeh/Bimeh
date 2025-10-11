<?php

namespace App\Services;

use App\Repositories\FundingTransactionRepository;
use App\Repositories\InsuranceTransactionRepository;
use App\Repositories\FamilyFundingAllocationRepository;
use App\Models\Family;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class FinancialReportService
{
    private FundingTransactionRepository $fundingTransactionRepo;
    private InsuranceTransactionRepository $insuranceTransactionRepo;
    private FamilyFundingAllocationRepository $familyFundingAllocationRepo;

    public function __construct(
        FundingTransactionRepository $fundingTransactionRepo,
        InsuranceTransactionRepository $insuranceTransactionRepo,
        FamilyFundingAllocationRepository $familyFundingAllocationRepo
    ) {
        $this->fundingTransactionRepo = $fundingTransactionRepo;
        $this->insuranceTransactionRepo = $insuranceTransactionRepo;
        $this->familyFundingAllocationRepo = $familyFundingAllocationRepo;
    }

    /**
     * دریافت تمام تراکنش‌ها
     *
     * @param array $filters
     * @return Collection
     */
    public function getAllTransactions(array $filters = []): Collection
    {
        try {
            $allTransactions = collect();

            // جمع‌آوری تراکنش‌ها از منابع مختلف
            $allTransactions = $allTransactions->merge($this->getFundingTransactions());
            $allTransactions = $allTransactions->merge($this->getFamilyAllocations());
            $allTransactions = $allTransactions->merge($this->getInsuranceAllocations());
            $allTransactions = $allTransactions->merge($this->getInsuranceImportLogs());
            $allTransactions = $allTransactions->merge($this->getInsurancePayments());
            $allTransactions = $allTransactions->merge($this->getInsuranceShares());
            $allTransactions = $allTransactions->merge($this->getShareAllocationLogs());

            // مرتب‌سازی بر اساس زمان
            return $allTransactions->sortByDesc('sort_timestamp')->values();
            
        } catch (\Exception $e) {
            Log::error('Error in getAllTransactions: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * دریافت تراکنش‌ها با صفحه‌بندی
     *
     * @param Request $request
     * @return LengthAwarePaginator
     */
    public function getTransactionsWithPagination(Request $request): LengthAwarePaginator
    {
        $perPage = $request->get('per_page', 15);
        $allTransactions = $this->getAllTransactions();

        // Manual pagination
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedItems = $allTransactions->slice($offset, $perPage);

        return new LengthAwarePaginator(
            $paginatedItems,
            $allTransactions->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
            ]
        );
    }

    /**
     * محاسبه خلاصه مالی
     *
     * @param Collection $transactions
     * @return array
     */
    public function calculateSummary(Collection $transactions): array
    {
        $totalCredit = $transactions->where('type', 'credit')->sum('amount');
        $totalDebit = $transactions->where('type', 'debit')->sum('amount');

        return [
            'total_credit' => $totalCredit,
            'total_debit' => $totalDebit,
            'balance' => $totalCredit - $totalDebit,
            'transactions_count' => $transactions->count()
        ];
    }

    /**
     * دریافت تراکنش‌های بودجه
     *
     * @return Collection
     */
    private function getFundingTransactions(): Collection
    {
        $transactions = collect();
        $fundingTransactions = $this->fundingTransactionRepo->getAllWithSource();

        foreach ($fundingTransactions as $transaction) {
            $isAllocated = $transaction->allocated ?? false;
            $title = $isAllocated ? 'تخصیص بودجه' : __('financial.transaction_types.budget_allocation');
            $type = $isAllocated ? 'debit' : 'credit';

            $transactions->push($this->formatTransaction([
                'id' => $transaction->id,
                'title' => $title,
                'amount' => $transaction->amount,
                'type' => $type,
                'date' => $transaction->created_at,
                'description' => $transaction->description ?? 'تراکنش مالی',
                'source' => $transaction->source->name ?? 'نامشخص',
            ]));
        }

        return $transactions;
    }

    /**
     * دریافت تخصیص‌های بودجه خانواده
     *
     * @return Collection
     */
    private function getFamilyAllocations(): Collection
    {
        $transactions = collect();
        $allocations = $this->familyFundingAllocationRepo->getAllWithRelations();

        foreach ($allocations as $alloc) {
            $membersCount = $alloc->family->members_count ?? 0;

            $transactions->push($this->formatTransaction([
                'id' => $alloc->id,
                'title' => 'تخصیص بودجه خانواده',
                'amount' => $alloc->amount,
                'type' => 'debit',
                'date' => $alloc->approved_at ?? $alloc->created_at,
                'description' => $alloc->description ?: 'تخصیص ' . $alloc->percentage . '% از حق بیمه',
                'reference_no' => 'ALLOC-' . $alloc->id,
                'details' => $alloc->fundingSource ? $alloc->fundingSource->name : 'منبع مالی نامشخص',
                'payment_id' => $alloc->id,
                'family_count' => 1,
                'members_count' => $membersCount,
                'family' => $alloc->family,
                'members' => collect(),
                'created_family_codes' => [],
                'updated_family_codes' => [],
                'allocation_type' => 'family_funding'
            ]));
        }

        return $transactions;
    }

    /**
     * دریافت تخصیص‌های بیمه منفرد
     *
     * @return Collection
     */
    private function getInsuranceAllocations(): Collection
    {
        $transactions = collect();
        $allocations = $this->insuranceTransactionRepo->getInsuranceAllocations();

        foreach ($allocations as $alloc) {
            $membersCount = $alloc->family->members_count ?? 0;

            $transactions->push($this->formatTransaction([
                'id' => $alloc->id,
                'title' => __('financial.transaction_types.premium_payment'),
                'amount' => $alloc->amount,
                'type' => 'debit',
                'date' => $alloc->created_at,
                'description' => $alloc->description,
                'reference_no' => null,
                'details' => null,
                'payment_id' => $alloc->id,
                'family_count' => 1,
                'members_count' => $membersCount,
                'family' => $alloc->family,
                'members' => collect(),
                'created_family_codes' => [],
                'updated_family_codes' => [],
            ]));
        }

        return $transactions;
    }

    /**
     * دریافت لاگ‌های ایمپورت بیمه - با رفع N+1 Query
     *
     * @return Collection
     */
    private function getInsuranceImportLogs(): Collection
    {
        $transactions = collect();
        $importLogs = $this->insuranceTransactionRepo->getImportLogs();
        $allFamilyCodes = collect();

        // جمع‌آوری تمام family codes برای رفع N+1
        foreach ($importLogs as $log) {
            $familyCodes = array_merge(
                is_array($log->created_family_codes) ? $log->created_family_codes : [],
                is_array($log->updated_family_codes) ? $log->updated_family_codes : []
            );
            $allFamilyCodes = $allFamilyCodes->merge($familyCodes);
        }

        // یک کوئری واحد برای دریافت تعداد اعضای تمام خانواده‌ها
        $membersCounts = [];
        if ($allFamilyCodes->isNotEmpty()) {
            $membersCounts = Family::whereIn('family_code', $allFamilyCodes->unique()->values()->toArray())
                ->withCount('members')
                ->get()
                ->pluck('members_count', 'family_code')
                ->toArray();
        }

        // پردازش لاگ‌ها
        foreach ($importLogs as $log) {
            $familyCodes = array_merge(
                is_array($log->created_family_codes) ? $log->created_family_codes : [],
                is_array($log->updated_family_codes) ? $log->updated_family_codes : []
            );
            $familyCount = count($familyCodes);

            // محاسبه تعداد اعضا از آرایه از پیش آماده شده
            $totalMembersCount = 0;
            foreach ($familyCodes as $familyCode) {
                $totalMembersCount += $membersCounts[$familyCode] ?? 0;
            }

            $transactions->push($this->formatTransaction([
                'id' => $log->id,
                'title' => __('financial.transaction_types.premium_import'),
                'amount' => $log->total_insurance_amount,
                'type' => 'debit',
                'date' => $log->created_at,
                'description' => 'ایمپورت اکسل: ' . ($log->file_name ?? ''),
                'reference_no' => null,
                'details' => null,
                'payment_id' => null,
                'family_count' => $familyCount,
                'members_count' => $totalMembersCount,
                'count_success' => $log->created_count + $log->updated_count,
                'members' => collect(),
                'family' => null,
                'updated_family_codes' => is_array($log->updated_family_codes) ? $log->updated_family_codes : [],
                'created_family_codes' => is_array($log->created_family_codes) ? $log->created_family_codes : [],
            ]));
        }

        return $transactions;
    }

    /**
     * دریافت پرداخت‌های بیمه سیستماتیک
     *
     * @return Collection
     */
    private function getInsurancePayments(): Collection
    {
        $transactions = collect();
        $payments = $this->insuranceTransactionRepo->getInsurancePayments();

        foreach ($payments as $payment) {
            $family = $payment->familyInsurance ? $payment->familyInsurance->family : null;
            $membersCount = $payment->insured_persons_count ?? ($family ? $family->members_count : 0);

            $transactions->push($this->formatTransaction([
                'id' => $payment->id,
                'title' => __('financial.transaction_types.premium_payment'),
                'amount' => $payment->total_amount,
                'type' => 'debit',
                'date' => $payment->payment_date ?? $payment->created_at,
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
            ]));
        }

        return $transactions;
    }

    /**
     * دریافت سهم‌های بیمه - فقط Manual Shares
     *
     * @return Collection
     */
    private function getInsuranceShares(): Collection
    {
        $transactions = collect();
        // فقط سهم‌های manual (بدون import_log_id) را بگیریم
        // سهم‌های bulk allocation در ShareAllocationLog محاسبه می‌شوند
        $shares = $this->insuranceTransactionRepo->getInsuranceShares();

        foreach ($shares as $share) {
            $family = $share->familyInsurance->family;
            $membersCount = $family->members_count ?? 0;

            $transactions->push($this->formatTransaction([
                'id' => 'share-' . $share->id,
                'title' => 'پرداخت سهم بیمه',
                'amount' => $share->amount,
                'type' => 'debit',
                'date' => $share->updated_at,
                'description' => "پرداخت سهم {$share->percentage}% برای خانواده " . $family->family_code,
                'reference_no' => 'SHARE-' . $share->id,
                'details' => $share->fundingSource ? $share->fundingSource->name : 'منبع مالی نامشخص',
                'payment_id' => $share->id,
                'family_count' => 1,
                'members_count' => $membersCount,
                'family' => $family,
                'members' => collect(),
                'created_family_codes' => [],
                'updated_family_codes' => [],
                'is_share' => true
            ]));
        }

        return $transactions;
    }

    /**
     * دریافت لاگ‌های تخصیص سهم گروهی
     *
     * @return Collection
     */
    private function getShareAllocationLogs(): Collection
    {
        $transactions = collect();
        $logs = $this->insuranceTransactionRepo->getShareAllocationLogs();

        foreach ($logs as $log) {
            $transactions->push($this->formatTransaction([
                'id' => 'alloc-' . $log->id,
                'title' => 'تخصیص سهم گروهی',
                'amount' => $log->total_amount,
                'type' => 'debit',
                'date' => $log->updated_at,
                'description' => $log->description,
                'details' => $log->families_count . ' خانواده',
                'payment_id' => $log->id,
                'family_count' => $log->families_count,
                'batch_id' => $log->batch_id,
            ]));
        }

        return $transactions;
    }

    /**
     * فرمت کردن تراکنش
     *
     * @param array $data
     * @return array
     */
    private function formatTransaction(array $data): array
    {
        $formatted = [
            'id' => $data['id'],
            'title' => $data['title'] ?? '',
            'amount' => $data['amount'] ?? 0,
            'type' => $data['type'] ?? 'debit',
            'date' => $data['date'] ?? now(),
            'date_formatted' => isset($data['date']) ? jdate($data['date'])->format('Y/m/d') : jdate(now())->format('Y/m/d'),
            'sort_timestamp' => isset($data['date']) ? $data['date']->timestamp : now()->timestamp,
            'description' => $data['description'] ?? '',
        ];

        // اضافه کردن فیلدهای اختیاری
        $optionalFields = [
            'source', 'reference_no', 'details', 'payment_id', 'family_count',
            'members_count', 'family', 'members', 'created_family_codes',
            'updated_family_codes', 'allocation_type', 'is_share', 'batch_id', 'count_success'
        ];

        foreach ($optionalFields as $field) {
            if (isset($data[$field])) {
                $formatted[$field] = $data[$field];
            }
        }

        return $formatted;
    }
    
    /**
     * بررسی محدودیت تعداد رکورد
     *
     * @param array $filters
     * @param int $maxRecords
     * @return array
     */
    public function validateRecordLimit(array $filters = [], int $maxRecords = 10000): array
    {
        try {
            $transactions = $this->getAllTransactions($filters);
            $count = $transactions->count();
            
            return [
                'valid' => $count <= $maxRecords,
                'count' => $count,
                'max' => $maxRecords,
                'message' => $count > $maxRecords 
                    ? "تعداد تراکنش‌ها ({$count}) بیش از حد مجاز ({$maxRecords}) است."
                    : "تعداد تراکنش‌ها: {$count}"
            ];
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error in validateRecordLimit: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * دریافت تعداد تراکنش‌ها
     *
     * @param array $filters
     * @return int
     */
    public function getTransactionsCount(array $filters = []): int
    {
        return $this->getAllTransactions($filters)->count();
    }
}
