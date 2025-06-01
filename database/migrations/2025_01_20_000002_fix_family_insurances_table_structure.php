<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('family_insurances', function (Blueprint $table) {
            // بررسی وجود ستون‌ها قبل از اضافه کردن
            if (!Schema::hasColumn('family_insurances', 'premium_amount')) {
                $table->decimal('premium_amount', 15, 2)->nullable()->comment('مبلغ حق بیمه');
            }
            
            if (!Schema::hasColumn('family_insurances', 'start_date')) {
                $table->date('start_date')->nullable();
            }
            
            if (!Schema::hasColumn('family_insurances', 'end_date')) {
                $table->date('end_date')->nullable();
            }
        });

        // بررسی وجود ستون‌های قدیمی قبل از انتقال داده
        if (Schema::hasColumn('family_insurances', 'insurance_amount')) {
            // کپی کردن داده‌ها از فیلدهای قدیمی به جدید
            DB::statement("UPDATE family_insurances SET premium_amount = insurance_amount WHERE insurance_amount IS NOT NULL");
        }
        
        if (Schema::hasColumn('family_insurances', 'insurance_issue_date')) {
            DB::statement("UPDATE family_insurances SET start_date = insurance_issue_date WHERE insurance_issue_date IS NOT NULL");
        }
        
        if (Schema::hasColumn('family_insurances', 'insurance_end_date')) {
            DB::statement("UPDATE family_insurances SET end_date = insurance_end_date WHERE insurance_end_date IS NOT NULL");
        }

        // بررسی وجود ستون‌های قدیمی قبل از حذف
        $columnsToRemove = [];
        if (Schema::hasColumn('family_insurances', 'insurance_amount')) {
            $columnsToRemove[] = 'insurance_amount';
        }
        
        if (Schema::hasColumn('family_insurances', 'insurance_issue_date')) {
            $columnsToRemove[] = 'insurance_issue_date';
        }
        
        if (Schema::hasColumn('family_insurances', 'insurance_end_date')) {
            $columnsToRemove[] = 'insurance_end_date';
        }
        
        if (!empty($columnsToRemove)) {
            Schema::table('family_insurances', function (Blueprint $table) use ($columnsToRemove) {
                // حذف فیلدهای قدیمی
                $table->dropColumn($columnsToRemove);
            });
        }
        
        // اضافه کردن ایندکس‌ها
        try {
            Schema::table('family_insurances', function (Blueprint $table) {
                $table->index(['family_id', 'insurance_type']);
            });
        } catch (\Exception $e) {
            // ایندکس ممکن است قبلاً وجود داشته باشد
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // این متد بازگشت به حالت قبل را انجام نمی‌دهد
        // چون ستون‌های قدیمی حذف شده‌اند و بازگشت خطرناک است
    }
}; 