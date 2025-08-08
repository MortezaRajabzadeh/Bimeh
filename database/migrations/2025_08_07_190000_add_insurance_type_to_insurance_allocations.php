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
            if (!Schema::hasColumn('insurance_allocations', 'insurance_type')) {
                $table->string('insurance_type', 100)->nullable()->after('description')->comment('نوع بیمه');
            }
            
            // اضافه کردن ایندکس برای جستجوی سریع‌تر
            try {
                $table->index('insurance_type', 'idx_insurance_type');
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
            // حذف ایندکس
            $table->dropIndex(['insurance_type']);
            
            // حذف ستون
            $table->dropColumn('insurance_type');
        });
    }
};
