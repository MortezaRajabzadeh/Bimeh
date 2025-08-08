<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('insurance_allocations', function (Blueprint $table) {
            // بررسی وجود ستون‌ها قبل از اضافه کردن
            if (!Schema::hasColumn('insurance_allocations', 'amount')) {
                $table->decimal('amount', 15, 2)->nullable()->comment('مبلغ خسارت پرداخت شده');
            }
            
            if (!Schema::hasColumn('insurance_allocations', 'issue_date')) {
                $table->string('issue_date')->nullable()->comment('تاریخ صدور بیمه‌نامه');
            }
            
            if (!Schema::hasColumn('insurance_allocations', 'paid_at')) {
                $table->string('paid_at')->nullable()->comment('تاریخ پرداخت خسارت');
            }
            
            if (!Schema::hasColumn('insurance_allocations', 'description')) {
                $table->text('description')->nullable()->comment('شرح خسارت');
            }
            
            if (!Schema::hasColumn('insurance_allocations', 'funding_transaction_id')) {
                $table->unsignedBigInteger('funding_transaction_id')->nullable();
                
                // اضافه کردن foreign key برای funding_transaction_id
                $table->foreign('funding_transaction_id')
                      ->references('id')
                      ->on('funding_transactions')
                      ->onDelete('set null');
            }
        });
        
        // اضافه کردن ایندکس‌ها در یک Schema::table جداگانه
        Schema::table('insurance_allocations', function (Blueprint $table) {
            // بررسی وجود ایندکس‌ها قبل از اضافه کردن
            try {
                $table->index(['family_id', 'amount'], 'idx_family_amount');
            } catch (\Exception $e) {
                // ایندکس از قبل وجود دارد
            }
            
            try {
                $table->index(['issue_date', 'paid_at'], 'idx_dates');
            } catch (\Exception $e) {
                // ایندکس از قبل وجود دارد
            }
            
            try {
                $table->index('funding_transaction_id', 'idx_funding_transaction');
            } catch (\Exception $e) {
                // ایندکس از قبل وجود دارد
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_allocations', function (Blueprint $table) {
            // حذف foreign key
            $table->dropForeign(['funding_transaction_id']);
            
            // حذف ایندکس‌ها
            $table->dropIndex(['family_id', 'amount']);
            $table->dropIndex(['issue_date', 'paid_at']);
            $table->dropIndex(['funding_transaction_id']);
            
            // حذف ستون‌های اضافه شده
            $table->dropColumn([
                'amount',
                'issue_date', 
                'paid_at',
                'description',
                'funding_transaction_id'
            ]);
        });
    }
};
