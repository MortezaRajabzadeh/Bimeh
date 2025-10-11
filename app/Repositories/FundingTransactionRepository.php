<?php

namespace App\Repositories;

use App\Models\FundingTransaction;
use Illuminate\Support\Collection;

class FundingTransactionRepository extends BaseRepository
{
    public function __construct(FundingTransaction $model)
    {
        parent::__construct($model);
    }

    /**
     * دریافت تمام تراکنش‌های بودجه با eager loading منبع مالی
     *
     * @return Collection
     */
    public function getAllWithSource(): Collection
    {
        $transactions = collect();
        
        $this->model
            ->select('id', 'funding_source_id', 'amount', 'description', 'allocated', 'created_at')
            ->with(['source' => function($query) {
                $query->select('id', 'name');
            }])
            ->chunk(500, function($chunk) use (&$transactions) {
                $transactions = $transactions->merge($chunk);
            });
            
        return $transactions;
    }

    /**
     * محاسبه مجموع مبالغ تراکنش‌ها
     *
     * @return float
     */
    public function getTotalAmount(): float
    {
        return $this->model->sum('amount') ?? 0;
    }

    /**
     * دریافت تراکنش‌های تخصیص یافته
     *
     * @return Collection
     */
    public function getAllocatedTransactions(): Collection
    {
        $transactions = collect();
        
        $this->model
            ->select('id', 'funding_source_id', 'amount', 'description', 'allocated', 'created_at')
            ->with(['source' => function($query) {
                $query->select('id', 'name');
            }])
            ->where('allocated', true)
            ->chunk(500, function($chunk) use (&$transactions) {
                $transactions = $transactions->merge($chunk);
            });
            
        return $transactions;
    }

    /**
     * دریافت تراکنش‌های تخصیص نیافته
     *
     * @return Collection
     */
    public function getNonAllocatedTransactions(): Collection
    {
        $transactions = collect();
        
        $this->model
            ->select('id', 'funding_source_id', 'amount', 'description', 'allocated', 'created_at')
            ->with(['source' => function($query) {
                $query->select('id', 'name');
            }])
            ->where('allocated', false)
            ->chunk(500, function($chunk) use (&$transactions) {
                $transactions = $transactions->merge($chunk);
            });
            
        return $transactions;
    }
}