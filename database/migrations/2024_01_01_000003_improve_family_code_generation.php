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
        // اضافه کردن ایندکس برای بهبود عملکرد جستجوی family_code
        if (Schema::hasTable('families')) {
            Schema::table('families', function (Blueprint $table) {
                // حذف ایندکس قدیمی اگر وجود داشته باشد
                try {
                    $table->dropUnique(['family_code']);
                } catch (Exception $e) {
                    // اگر ایندکس وجود نداشت، نادیده بگیر
                }
                
                // ایجاد ایندکس جدید
                $table->unique('family_code', 'families_family_code_unique_new');
            });
        }
        
        // پاک کردن family_code های تکراری (در صورت وجود)
        if (Schema::hasTable('families')) {
            DB::statement("
                DELETE f1 FROM families f1
                INNER JOIN families f2 
                WHERE f1.id > f2.id 
                AND f1.family_code = f2.family_code
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('families')) {
            Schema::table('families', function (Blueprint $table) {
                try {
                    $table->dropUnique('families_family_code_unique_new');
                } catch (Exception $e) {
                    // نادیده بگیر
                }
            });
        }
    }
}; 