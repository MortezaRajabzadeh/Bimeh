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
        Schema::table('funding_transactions', function (Blueprint $table) {
            // اضافه کردن ستون status برای مدیریت وضعیت تراکنش‌ها
            if (!Schema::hasColumn('funding_transactions', 'status')) {
                $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed')->after('description');
            }
            
            // اضافه کردن ستون reference_no اگر وجود ندارد
            if (!Schema::hasColumn('funding_transactions', 'reference_no')) {
                $table->string('reference_no')->nullable()->after('status');
            }
            
            // اضافه کردن ستون allocated برای مشخص کردن تراکنش‌های تخصیص داده شده
            if (!Schema::hasColumn('funding_transactions', 'allocated')) {
                $table->boolean('allocated')->default(false)->after('reference_no');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funding_transactions', function (Blueprint $table) {
            // حذف ستون‌های اضافه شده
            $columns = ['status', 'reference_no', 'allocated'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('funding_transactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
