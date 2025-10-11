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
     * این migration index‌های بهینه‌ساز را به جدول family_insurances اضافه می‌کند
     * برای بهبود عملکرد whereHas queries و فیلترهای status
     * 
     * دلیل جداسازی:
     * - جدول family_insurances در migration اصلی create_microbime_core_tables ایجاد شده
     * - Index موجود فقط (family_id, insurance_type) است
     * - نیاز به index‌های اضافی برای کوئری‌های پیچیده‌تر
     */
    public function up(): void
    {
        $startTime = microtime(true);
        $indexesAdded = 0;

        Schema::table('family_insurances', function (Blueprint $table) use (&$indexesAdded) {
            // Index روی status برای فیلترهای ساده
            // کوئری: FinancialReportController->index() (خط 48)
            // کوئری: InsuranceTransactionRepository (خط 89)
            // مثال: WHERE status = 'active' یا WHERE status = 'insured'
            try {
                $table->index('status', 'idx_family_insurances_status');
                $indexesAdded++;
                Log::info('Added index: idx_family_insurances_status');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_family_insurances_status - ' . $e->getMessage());
            }

            // Composite index روی (family_id, status) برای whereHas queries
            // کوئری: InsuranceTransactionRepository->getInsuranceShares() (خط 88-89)
            // مثال: whereHas('familyInsurance', function($q) { $q->where('status', 'insured'); })
            // این index بهبود چشمگیری در عملکرد JOIN queries ایجاد می‌کند
            // نکته: index موجود (family_id, insurance_type) این مورد را پوشش نمی‌دهد
            try {
                $table->index(['family_id', 'status'], 'idx_family_insurances_family_status');
                $indexesAdded++;
                Log::info('Added index: idx_family_insurances_family_status');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_family_insurances_family_status - ' . $e->getMessage());
            }

            // Index روی created_at برای ordering و date filters
            // کوئری: InsuranceShareService (whereIn family_id + latest)
            // مثال: ORDER BY created_at DESC یا latest()
            try {
                $table->index('created_at', 'idx_family_insurances_created_at');
                $indexesAdded++;
                Log::info('Added index: idx_family_insurances_created_at');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_family_insurances_created_at - ' . $e->getMessage());
            }

            // Composite index روی (status, premium_amount) برای فیلترهای ترکیبی
            // کوئری: InsuranceShareService
            // مثال: WHERE status = 'active' AND premium_amount > 0
            // ترتیب: status (equality) اول، premium_amount (range) دوم
            try {
                $table->index(['status', 'premium_amount'], 'idx_family_insurances_status_premium');
                $indexesAdded++;
                Log::info('Added index: idx_family_insurances_status_premium');
            } catch (\Exception $e) {
                Log::warning('Index already exists or failed: idx_family_insurances_status_premium - ' . $e->getMessage());
            }
        });

        $duration = round(microtime(true) - $startTime, 2);
        Log::info("Family Insurances Indexes Migration completed: {$indexesAdded} indexes added in {$duration} seconds");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $startTime = microtime(true);
        $indexesRemoved = 0;

        Schema::table('family_insurances', function (Blueprint $table) use (&$indexesRemoved) {
            try {
                $table->dropIndex('idx_family_insurances_status_premium');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_family_insurances_status_premium - ' . $e->getMessage());
            }

            try {
                $table->dropIndex('idx_family_insurances_created_at');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_family_insurances_created_at - ' . $e->getMessage());
            }

            try {
                $table->dropIndex('idx_family_insurances_family_status');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_family_insurances_family_status - ' . $e->getMessage());
            }

            try {
                $table->dropIndex('idx_family_insurances_status');
                $indexesRemoved++;
            } catch (\Exception $e) {
                Log::warning('Failed to drop index: idx_family_insurances_status - ' . $e->getMessage());
            }
        });

        $duration = round(microtime(true) - $startTime, 2);
        Log::info("Family Insurances Indexes Migration rolled back: {$indexesRemoved} indexes removed in {$duration} seconds");
    }
};
