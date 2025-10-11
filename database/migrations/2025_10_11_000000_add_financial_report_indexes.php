<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * این migration index‌های بهینه‌ساز را به جداول مالی اضافه می‌کند
     * بر اساس تحلیل الگوهای کوئری در Repository‌ها و Service‌ها
     * 
     * برای تحلیل slow queries:
     * 1. فعال کردن query log: DB::enableQueryLog() در AppServiceProvider
     * 2. مشاهده queries: dd(DB::getQueryLog())
     * 3. استفاده از Laravel Telescope: /telescope/queries
     * 4. تحلیل با EXPLAIN: DB::select('EXPLAIN SELECT ...')
     * 5. استفاده از Commands: php artisan analyze:slow-queries
     */
    public function up(): void
    {
        $startTime = microtime(true);
        $indexesAdded = 0;

        // ========================================
        // 1. funding_transactions INDEXES
        // ========================================
        
        Schema::table('funding_transactions', function (Blueprint $table) use (&$indexesAdded) {
            // Index روی created_at برای date range queries
            // کوئری: FinancialReportController->estimateTransactionCount() (خطوط 320-323)
            // مثال: WHERE created_at >= '2025-01-01' AND created_at <= '2025-12-31'
            try {
                $table->index('created_at', 'idx_funding_transactions_created_at');
                $indexesAdded++;
                Log::info('Added index: idx_funding_transactions_created_at');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_funding_transactions_created_at - ' . $e->getMessage());
            }

            // Index روی allocated برای فیلتر allocated/non-allocated
            // کوئری: FundingTransactionRepository->getAllocatedTransactions() (خط 60)
            // مثال: WHERE allocated = true
            try {
                $table->index('allocated', 'idx_funding_transactions_allocated');
                $indexesAdded++;
                Log::info('Added index: idx_funding_transactions_allocated');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_funding_transactions_allocated - ' . $e->getMessage());
            }

            // Composite index روی (status, created_at) برای کوئری‌های ترکیبی
            // کوئری: PaidClaims Livewire (خط 97)
            // مثال: WHERE status = 'completed' ORDER BY created_at DESC
            // ترتیب: status (equality) اول، created_at (range/order) دوم
            try {
                $table->index(['status', 'created_at'], 'idx_funding_transactions_status_created');
                $indexesAdded++;
                Log::info('Added index: idx_funding_transactions_status_created');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_funding_transactions_status_created - ' . $e->getMessage());
            }
        });

        // ========================================
        // 2. insurance_shares INDEXES
        // ========================================
        
        Schema::table('insurance_shares', function (Blueprint $table) use (&$indexesAdded) {
            // Composite index روی (family_insurance_id, amount)
            // کوئری: InsuranceTransactionRepository->getInsuranceShares() (خط 91)
            // مثال: WHERE family_insurance_id = X AND amount > 0
            // نکته: index موجود (family_insurance_id, payer_type) این مورد را پوشش نمی‌دهد
            try {
                $table->index(['family_insurance_id', 'amount'], 'idx_insurance_shares_family_amount');
                $indexesAdded++;
                Log::info('Added index: idx_insurance_shares_family_amount');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_insurance_shares_family_amount - ' . $e->getMessage());
            }

            // Index روی import_log_id برای whereNull/whereNotNull
            // کوئری: InsuranceTransactionRepository (خط 94)
            // مثال: WHERE import_log_id IS NULL
            // دلیل: جداسازی manual shares از bulk allocations
            try {
                $table->index('import_log_id', 'idx_insurance_shares_import_log');
                $indexesAdded++;
                Log::info('Added index: idx_insurance_shares_import_log');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_insurance_shares_import_log - ' . $e->getMessage());
            }
        });

        // ========================================
        // 3. share_allocation_logs INDEXES
        // ========================================
        
        Schema::table('share_allocation_logs', function (Blueprint $table) use (&$indexesAdded) {
            // Composite index روی (status, total_amount)
            // کوئری: InsuranceTransactionRepository->getShareAllocationLogs() (خطوط 131-132)
            // مثال: WHERE status = 'completed' AND total_amount > 0
            // ترتیب: status (equality) اول، total_amount (range) دوم
            try {
                $table->index(['status', 'total_amount'], 'idx_share_logs_status_amount');
                $indexesAdded++;
                Log::info('Added index: idx_share_logs_status_amount');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_share_logs_status_amount - ' . $e->getMessage());
            }

            // Index روی updated_at برای ordering
            // کوئری: Service (خط 364)
            // مثال: ORDER BY updated_at DESC
            try {
                $table->index('updated_at', 'idx_share_logs_updated_at');
                $indexesAdded++;
                Log::info('Added index: idx_share_logs_updated_at');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_share_logs_updated_at - ' . $e->getMessage());
            }

            // Index روی batch_id برای جستجوی batch‌های خاص
            // مثال: WHERE batch_id = 'xxx'
            try {
                $table->index('batch_id', 'idx_share_logs_batch_id');
                $indexesAdded++;
                Log::info('Added index: idx_share_logs_batch_id');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_share_logs_batch_id - ' . $e->getMessage());
            }

            // Index روی file_hash برای duplicate validation
            // کوئری: ShareAllocationLog->isDuplicateByFileHash()
            // مثال: WHERE file_hash = 'xxx' AND created_at >= '2025-01-01'
            try {
                $table->index('file_hash', 'idx_share_logs_file_hash');
                $indexesAdded++;
                Log::info('Added index: idx_share_logs_file_hash');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_share_logs_file_hash - ' . $e->getMessage());
            }
        });

        // ========================================
        // 4. family_funding_allocations INDEXES
        // ========================================
        
        Schema::table('family_funding_allocations', function (Blueprint $table) use (&$indexesAdded) {
            // Composite index روی (status, transaction_id)
            // کوئری: FamilyFundingAllocationRepository->getAllWithRelations() (خطوط 34-35)
            // مثال: WHERE status != 'pending' AND transaction_id IS NULL
            // ترتیب: status اول (equality/inequality)، transaction_id دوم (null check)
            try {
                $table->index(['status', 'transaction_id'], 'idx_family_funding_status_transaction');
                $indexesAdded++;
                Log::info('Added index: idx_family_funding_status_transaction');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_family_funding_status_transaction - ' . $e->getMessage());
            }

            // Index روی approved_at برای ordering
            // کوئری: Service (خط 153)
            // مثال: ORDER BY approved_at DESC
            try {
                $table->index('approved_at', 'idx_family_funding_approved_at');
                $indexesAdded++;
                Log::info('Added index: idx_family_funding_approved_at');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_family_funding_approved_at - ' . $e->getMessage());
            }

            // Composite index روی (funding_source_id, status) برای محاسبات sum
            // کوئری: FamilyFundingAllocationService
            // مثال: WHERE funding_source_id = X AND status != 'pending' SUM(amount)
            try {
                $table->index(['funding_source_id', 'status'], 'idx_family_funding_source_status');
                $indexesAdded++;
                Log::info('Added index: idx_family_funding_source_status');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_family_funding_source_status - ' . $e->getMessage());
            }
        });

        // ========================================
        // 5. insurance_allocations INDEXES
        // ========================================
        
        Schema::table('insurance_allocations', function (Blueprint $table) use (&$indexesAdded) {
            // Index روی created_at برای date range queries
            // کوئری: DashboardStats
            // مثال: WHERE created_at BETWEEN '2025-01-01' AND '2025-12-31'
            try {
                $table->index('created_at', 'idx_insurance_allocations_created_at');
                $indexesAdded++;
                Log::info('Added index: idx_insurance_allocations_created_at');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_insurance_allocations_created_at - ' . $e->getMessage());
            }

            // Composite index روی (family_id, amount, issue_date) برای duplicate check
            // کوئری: ClaimsImportService
            // مثال: WHERE family_id = X AND amount = Y AND issue_date = Z
            // نکته: index موجود (family_id, amount) بخشی از این نیاز را پوشش می‌دهد
            try {
                $table->index(['family_id', 'amount', 'issue_date'], 'idx_insurance_allocations_duplicate_check');
                $indexesAdded++;
                Log::info('Added index: idx_insurance_allocations_duplicate_check');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_insurance_allocations_duplicate_check - ' . $e->getMessage());
            }
        });

        $duration = round(microtime(true) - $startTime, 2);
        Log::info("Financial Report Indexes Migration completed: {$indexesAdded} indexes added in {$duration} seconds");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $startTime = microtime(true);
        $indexesRemoved = 0;

        // حذف به ترتیب معکوس
        
        Schema::table('insurance_allocations', function (Blueprint $table) use (&$indexesRemoved) {
            try {
                $table->dropIndex('idx_insurance_allocations_duplicate_check');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_insurance_allocations_duplicate_check - ' . $e->getMessage());
            }

            try {
                $table->dropIndex('idx_insurance_allocations_created_at');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_insurance_allocations_created_at - ' . $e->getMessage());
            }
        });

        Schema::table('family_funding_allocations', function (Blueprint $table) use (&$indexesRemoved) {
            try {
                $table->dropIndex('idx_family_funding_source_status');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_family_funding_source_status - ' . $e->getMessage());
            }

            try {
                $table->dropIndex('idx_family_funding_approved_at');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_family_funding_approved_at - ' . $e->getMessage());
            }

            try {
                $table->dropIndex('idx_family_funding_status_transaction');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_family_funding_status_transaction - ' . $e->getMessage());
            }
        });

        Schema::table('share_allocation_logs', function (Blueprint $table) use (&$indexesRemoved) {
            try {
                $table->dropIndex('idx_share_logs_file_hash');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_share_logs_file_hash - ' . $e->getMessage());
            }

            try {
                $table->dropIndex('idx_share_logs_batch_id');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_share_logs_batch_id - ' . $e->getMessage());
            }

            try {
                $table->dropIndex('idx_share_logs_updated_at');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_share_logs_updated_at - ' . $e->getMessage());
            }

            try {
                $table->dropIndex('idx_share_logs_status_amount');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_share_logs_status_amount - ' . $e->getMessage());
            }
        });

        Schema::table('insurance_shares', function (Blueprint $table) use (&$indexesRemoved) {
            try {
                $table->dropIndex('idx_insurance_shares_import_log');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_insurance_shares_import_log - ' . $e->getMessage());
            }

            try {
                $table->dropIndex('idx_insurance_shares_family_amount');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_insurance_shares_family_amount - ' . $e->getMessage());
            }
        });

        Schema::table('funding_transactions', function (Blueprint $table) use (&$indexesRemoved) {
            try {
                $table->dropIndex('idx_funding_transactions_status_created');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_funding_transactions_status_created - ' . $e->getMessage());
            }

            try {
                $table->dropIndex('idx_funding_transactions_allocated');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_funding_transactions_allocated - ' . $e->getMessage());
            }

            try {
                $table->dropIndex('idx_funding_transactions_created_at');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_funding_transactions_created_at - ' . $e->getMessage());
            }
        });

        $duration = round(microtime(true) - $startTime, 2);
        Log::info("Financial Report Indexes Migration rolled back: {$indexesRemoved} indexes removed in {$duration} seconds");
    }
};
