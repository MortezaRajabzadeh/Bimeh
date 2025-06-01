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
            // اضافه کردن ستون funding_source_id به جدول
            if (!Schema::hasColumn('insurance_shares', 'funding_source_id')) {
                $table->unsignedBigInteger('funding_source_id')->nullable()->after('payer_organization_id');
                $table->foreign('funding_source_id')->references('id')->on('funding_sources')->onDelete('set null');
                
                // ایجاد شاخص برای ستون جدید
                $table->index('funding_source_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_shares', function (Blueprint $table) {
            // حذف foreign key و ستون
            if (Schema::hasColumn('insurance_shares', 'funding_source_id')) {
                $table->dropForeign(['funding_source_id']);
                $table->dropIndex(['funding_source_id']);
                $table->dropColumn('funding_source_id');
            }
        });
    }
}; 