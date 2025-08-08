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
        // حذف foreign key constraint
        Schema::table('insurance_allocations', function (Blueprint $table) {
            $table->dropForeign(['funding_transaction_id']);
        });
        
        // تغییر ستون به nullable
        DB::statement('ALTER TABLE insurance_allocations MODIFY funding_transaction_id bigint unsigned NULL');
        
        // اضافه کردن foreign key با onDelete('set null')
        Schema::table('insurance_allocations', function (Blueprint $table) {
            $table->foreign('funding_transaction_id')
                  ->references('id')
                  ->on('funding_transactions')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف foreign key constraint
        Schema::table('insurance_allocations', function (Blueprint $table) {
            $table->dropForeign(['funding_transaction_id']);
        });
        
        // تغییر ستون به NOT NULL
        DB::statement('ALTER TABLE insurance_allocations MODIFY funding_transaction_id bigint unsigned NOT NULL');
        
        // اضافه کردن foreign key
        Schema::table('insurance_allocations', function (Blueprint $table) {
            $table->foreign('funding_transaction_id')
                  ->references('id')
                  ->on('funding_transactions')
                  ->onDelete('restrict');
        });
    }
};
