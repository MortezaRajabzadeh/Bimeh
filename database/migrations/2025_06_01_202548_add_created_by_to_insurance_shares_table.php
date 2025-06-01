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
        Schema::table('insurance_shares', function (Blueprint $table) {
            // اضافه کردن ستون created_by به جدول
            if (!Schema::hasColumn('insurance_shares', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('description')->constrained('users')->onDelete('set null');
                
                // اضافه کردن ستون import_log_id که در خطا نشان داده شده
                if (!Schema::hasColumn('insurance_shares', 'import_log_id')) {
                    $table->foreignId('import_log_id')->nullable()->after('created_by');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_shares', function (Blueprint $table) {
            // حذف foreign key و ستون‌ها
            if (Schema::hasColumn('insurance_shares', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            
            if (Schema::hasColumn('insurance_shares', 'import_log_id')) {
                $table->dropColumn('import_log_id');
            }
        });
    }
}; 