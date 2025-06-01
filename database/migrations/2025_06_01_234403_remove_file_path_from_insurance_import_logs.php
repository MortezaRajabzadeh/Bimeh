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
        Schema::table('insurance_import_logs', function (Blueprint $table) {
            // بررسی وجود ستون file_path و سپس حذف آن
            if (Schema::hasColumn('insurance_import_logs', 'file_path')) {
                $table->dropColumn('file_path');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_import_logs', function (Blueprint $table) {
            // اضافه کردن مجدد ستون file_path در صورت rollback
            if (!Schema::hasColumn('insurance_import_logs', 'file_path')) {
                $table->string('file_path')->nullable()->after('file_name');
            }
        });
    }
};
