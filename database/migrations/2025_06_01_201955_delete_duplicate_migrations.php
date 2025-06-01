<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * اجرای مایگریشن برای حذف مایگریشن‌های تکراری
     */
    public function up(): void
    {
        // لیست مایگریشن‌های تکراری که باید حذف شوند
        $duplicateMigrations = [
            // مایگریشن‌های تکراری جدول users
            '2025_06_01_201242_add_username_to_users_table',
            
            // مایگریشن‌های مرتبط با جدول funding allocations
            '2025_06_01_122838_add_transaction_id_to_family_funding_allocations_table',
            '2025_06_01_130534_add_import_log_id_to_family_funding_allocations_table',
            '2025_06_01_133348_add_unique_constraint_to_family_funding_allocations_table',
            '2025_06_01_133525_add_unique_import_identifier_to_family_funding_allocations_table',
        ];

        // حذف مایگریشن‌های تکراری از جدول
        DB::table('migrations')->whereIn('migration', $duplicateMigrations)->delete();
        
        // علامت‌گذاری برخی مایگریشن‌ها به عنوان اجرا شده (در صورتی که قبلاً اجرا نشده باشند)
        $this->markAsMigrated('2025_05_29_101546_create_family_criteria_table');
        $this->markAsMigrated('2025_06_01_004828_create_family_funding_allocations_table');
        $this->markAsMigrated('2025_06_01_004834_create_benefactors_table');
        $this->markAsMigrated('2025_06_01_011811_create_family_documents_table');
        $this->markAsMigrated('2025_06_01_013504_create_committee_decisions_table');
    }

    /**
     * برگرداندن تغییرات انجام شده در مایگریشن
     */
    public function down(): void
    {
        // عملیات برگشت ندارد زیرا حذف مایگریشن‌های تکراری یک عملیات یک‌طرفه است
    }
    
    /**
     * علامت‌گذاری یک مایگریشن به عنوان اجرا شده
     */
    private function markAsMigrated($migration)
    {
        if (!DB::table('migrations')->where('migration', $migration)->exists()) {
            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => DB::table('migrations')->max('batch') + 1
            ]);
        }
    }
};
