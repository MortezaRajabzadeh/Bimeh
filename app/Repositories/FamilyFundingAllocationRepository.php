<?php

namespace App\Repositories;

use App\Models\FamilyFundingAllocation;
use Illuminate\Support\Collection;

class FamilyFundingAllocationRepository extends BaseRepository
{
    public function __construct(FamilyFundingAllocation $model)
    {
        parent::__construct($model);
    }

    /**
     * دریافت تمام تخصیص‌های بودجه خانواده با روابط
     *
     * @return Collection
     */
    public function getAllWithRelations(): Collection
    {
        $allocations = collect();
        
        $this->model
            ->select('id', 'family_id', 'funding_source_id', 'transaction_id', 'amount', 'percentage', 'description', 'status', 'approved_at', 'created_at')
            ->with([
                'family' => function($query) {
                    $query->select('id', 'family_code')->withCount('members');
                },
                'fundingSource' => function($query) {
                    $query->select('id', 'name');
                }
            ])
            ->where('status', '!=', FamilyFundingAllocation::STATUS_PENDING)
            ->whereNull('transaction_id') // جلوگیری از دوبار شمارش
            ->chunk(500, function($chunk) use (&$allocations) {
                $allocations = $allocations->merge($chunk);
            });
            
        return $allocations;
    }

    /**
     * محاسبه مجموع مبالغ تخصیص یافته
     *
     * @return float
     */
    public function getTotalAllocatedAmount(): float
    {
        return $this->model
            ->where('status', '!=', FamilyFundingAllocation::STATUS_PENDING)
            ->sum('amount') ?? 0;
    }

    /**
     * دریافت تخصیص‌های تایید شده
     *
     * @return Collection
     */
    public function getApprovedAllocations(): Collection
    {
        $allocations = collect();
        
        $this->model
            ->select('id', 'family_id', 'funding_source_id', 'transaction_id', 'amount', 'percentage', 'description', 'status', 'approved_at', 'created_at')
            ->with([
                'family' => function($query) {
                    $query->select('id', 'family_code')->withCount('members');
                },
                'fundingSource' => function($query) {
                    $query->select('id', 'name');
                }
            ])
            ->where('status', FamilyFundingAllocation::STATUS_APPROVED)
            ->chunk(500, function($chunk) use (&$allocations) {
                $allocations = $allocations->merge($chunk);
            });
            
        return $allocations;
    }

    /**
     * دریافت تخصیص‌های پرداخت شده
     *
     * @return Collection
     */
    public function getPaidAllocations(): Collection
    {
        $allocations = collect();
        
        $this->model
            ->select('id', 'family_id', 'funding_source_id', 'transaction_id', 'amount', 'percentage', 'description', 'status', 'approved_at', 'created_at')
            ->with([
                'family' => function($query) {
                    $query->select('id', 'family_code')->withCount('members');
                },
                'fundingSource' => function($query) {
                    $query->select('id', 'name');
                }
            ])
            ->where('status', FamilyFundingAllocation::STATUS_PAID)
            ->chunk(500, function($chunk) use (&$allocations) {
                $allocations = $allocations->merge($chunk);
            });
            
        return $allocations;
    }
}