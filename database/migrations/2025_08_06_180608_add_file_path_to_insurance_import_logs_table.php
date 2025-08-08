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
            $table->string('file_path')->nullable()->after('file_name')->comment('مسیر فایل آپلود شده');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_import_logs', function (Blueprint $table) {
            $table->dropColumn('file_path');
        });
    }
};
