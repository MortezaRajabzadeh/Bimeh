<?php

namespace App\Repositories;

use App\Models\InsuranceAllocation;
use App\Models\InsurancePayment;
use App\Models\InsuranceShare;
use App\Models\InsuranceImportLog;
use App\Models\ShareAllocationLog;
use Illuminate\Support\Collection;

class InsuranceTransactionRepository
{
    /**
     * دریافت تخصیص‌های بیمه منفرد
     *
     * @return Collection
     */
    public function getInsuranceAllocations(): Collection
    {
        $allocations = collect();
        
        InsuranceAllocation::select('id', 'family_id', 'amount', 'description', 'created_at')
            ->with([
                'family' => function($query) {
                    $query->select('id', 'family_code')->withCount('members');
                }
            ])
            ->chunk(500, function($chunk) use (&$allocations) {
                $allocations = $allocations->merge($chunk);
            });
            
        return $allocations;
    }

    /**
     * دریافت پرداخت‌های بیمه سیستماتیک
     *
     * @return Collection
     */
    public function getInsurancePayments(): Collection
    {
        $payments = collect();
        
        InsurancePayment::select('id', 'family_insurance_id', 'total_amount', 'payment_date', 'created_at', 'description', 'transaction_reference', 'insured_persons_count')
            ->with([
                'familyInsurance' => function($query) {
                    $query->select('id', 'family_id');
                },
                'familyInsurance.family' => function($query) {
                    $query->select('id', 'family_code')->withCount('members');
                },
                'details' => function($query) {
                    $query->select('id', 'insurance_payment_id', 'member_id');
                },
                'details.member' => function($query) {
                    $query->select('id', 'first_name', 'last_name');
                }
            ])
            ->chunk(500, function($chunk) use (&$payments) {
                $payments = $payments->merge($chunk);
            });
            
        return $payments;
    }

    /**
     * دریافت سهم‌های بیمه - فقط Manual Shares
     *
     * @return Collection
     */
    public function getInsuranceShares(): Collection
    {
        $shares = collect();

        InsuranceShare::select('id', 'family_insurance_id', 'funding_source_id', 'amount', 'percentage', 'updated_at')
            ->with([
                'familyInsurance' => function($query) {
                    $query->select('id', 'family_id');
                },
                'familyInsurance.family' => function($query) {
                    $query->select('id', 'family_code')->withCount('members');
                },
                'fundingSource' => function($query) {
                    $query->select('id', 'name');
                }
            ])
            ->whereHas('familyInsurance', function($query) {
                $query->where('status', 'insured');
            })
            ->where('amount', '>', 0)
            // فقط سهم‌های manual (بدون import_log_id)
            // سهم‌های bulk allocation در ShareAllocationLog محاسبه می‌شوند
            ->whereNull('import_log_id')
            ->chunk(500, function($items) use (&$shares) {
                foreach ($items as $share) {
                    $shares->push($share);
                }
            });

        return $shares;
    }
    
    /**
     * دریافت لاگ‌های ایمپورت بیمه
     *
     * @return Collection
     */
    public function getImportLogs(): Collection
    {
        $logs = collect();
        
        InsuranceImportLog::select('id', 'total_insurance_amount', 'created_at', 'file_name', 'created_count', 'updated_count', 'created_family_codes', 'updated_family_codes')
            ->chunk(500, function($chunk) use (&$logs) {
                $logs = $logs->merge($chunk);
            });
            
        return $logs;
    }

    /**
     * دریافت لاگ‌های تخصیص سهم گروهی
     *
     * @return Collection
     */
    public function getShareAllocationLogs(): Collection
    {
        $logs = collect();
        
        ShareAllocationLog::select('id', 'total_amount', 'updated_at', 'description', 'families_count', 'batch_id')
            ->where('status', 'completed')
            ->where('total_amount', '>', 0)
            ->chunk(500, function($chunk) use (&$logs) {
                $logs = $logs->merge($chunk);
            });
            
        return $logs;
    }
}