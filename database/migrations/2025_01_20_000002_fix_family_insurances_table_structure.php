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
            // ابتدا فیلدهای جدید با نام‌های صحیح اضافه می‌کنیم
            $table->decimal('premium_amount', 15, 2)->nullable()->comment('مبلغ حق بیمه');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });

        // کپی کردن داده‌ها از فیلدهای قدیمی به جدید
        DB::statement("UPDATE family_insurances SET premium_amount = insurance_amount WHERE insurance_amount IS NOT NULL");
        DB::statement("UPDATE family_insurances SET start_date = insurance_issue_date WHERE insurance_issue_date IS NOT NULL");
        DB::statement("UPDATE family_insurances SET end_date = insurance_end_date WHERE insurance_end_date IS NOT NULL");

        Schema::table('family_insurances', function (Blueprint $table) {
            // حذف فیلدهای قدیمی
            $table->dropColumn([
                'insurance_amount',
                'insurance_issue_date', 
                'insurance_end_date'
            ]);
            
            // اضافه کردن index برای بهبود کارایی
            $table->index(['family_id', 'insurance_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_insurances', function (Blueprint $table) {
            // اضافه کردن فیلدهای قدیمی
            $table->bigInteger('insurance_amount')->unsigned()->nullable();
            $table->date('insurance_issue_date')->nullable();
            $table->date('insurance_end_date')->nullable();
        });

        // کپی کردن داده‌ها از فیلدهای جدید به قدیمی
        DB::statement("UPDATE family_insurances SET insurance_amount = premium_amount WHERE premium_amount IS NOT NULL");
        DB::statement("UPDATE family_insurances SET insurance_issue_date = start_date WHERE start_date IS NOT NULL");
        DB::statement("UPDATE family_insurances SET insurance_end_date = end_date WHERE end_date IS NOT NULL");

        Schema::table('family_insurances', function (Blueprint $table) {
            // حذف فیلدهای جدید
            $table->dropColumn([
                'premium_amount',
                'start_date',
                'end_date'
            ]);
        });
    }
}; 