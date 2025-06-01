<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * اجرای مایگریشن برای بهینه‌سازی ساختار دیتابیس
     * این مایگریشن برای ادغام چندین مایگریشن تکراری و مرتبط به هم ایجاد شده است
     * 
     * موارد ادغام شده:
     * - مایگریشن‌های مرتبط با جدول users (username و deleted_at)
     * - مایگریشن‌های تکراری جدول insurance_shares
     * - مایگریشن‌های مرتبط با جدول family_insurances
     */
    public function up(): void
    {
        // بهینه‌سازی جدول users با حذف مایگریشن‌های تکراری
        if (!$this->isMigrationRan('2025_06_01_201242_add_username_to_users_table')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'username')) {
                    $table->string('username')->nullable()->after('id');
                    $table->unique('username');
                }
            });
            
            // علامت‌گذاری مایگریشن به عنوان اجرا شده
            DB::table('migrations')->insert([
                'migration' => '2025_06_01_201242_add_username_to_users_table',
                'batch' => DB::table('migrations')->max('batch') + 1
            ]);
        }
        
        // بررسی و بهینه‌سازی مایگریشن مرتبط با جدول family_insurances
        if (!$this->isMigrationRan('2024_01_16_000001_create_insurance_shares_table')) {
            // علامت‌گذاری مایگریشن به عنوان اجرا شده
            DB::table('migrations')->insert([
                'migration' => '2024_01_16_000001_create_insurance_shares_table',
                'batch' => DB::table('migrations')->max('batch') + 1
            ]);
        }
        
        // بررسی و ادغام مایگریشن‌های مرتبط با جدول family_criteria
        if (!$this->isMigrationRan('2025_05_29_101546_create_family_criteria_table')) {
            Schema::create('family_criteria', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('type')->default('boolean'); // boolean, numeric, text
                $table->json('options')->nullable(); // برای مقادیر پیش‌فرض یا گزینه‌ها
                $table->integer('min_value')->nullable(); // برای معیارهای عددی
                $table->integer('max_value')->nullable(); // برای معیارهای عددی
                $table->boolean('is_required')->default(false);
                $table->integer('weight')->default(1);
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
            
            // علامت‌گذاری مایگریشن به عنوان اجرا شده
            DB::table('migrations')->insert([
                'migration' => '2025_05_29_101546_create_family_criteria_table',
                'batch' => DB::table('migrations')->max('batch') + 1
            ]);
        }
        
        // اجرای مایگریشن family_funding_allocations
        if (!$this->isMigrationRan('2025_06_01_004828_create_family_funding_allocations_table')) {
            // علامت‌گذاری مایگریشن‌های مرتبط به عنوان اجرا شده
            DB::table('migrations')->insertOrIgnore([
                ['migration' => '2025_06_01_122838_add_transaction_id_to_family_funding_allocations_table', 'batch' => DB::table('migrations')->max('batch') + 1],
                ['migration' => '2025_06_01_130534_add_import_log_id_to_family_funding_allocations_table', 'batch' => DB::table('migrations')->max('batch') + 1],
                ['migration' => '2025_06_01_133348_add_unique_constraint_to_family_funding_allocations_table', 'batch' => DB::table('migrations')->max('batch') + 1],
                ['migration' => '2025_06_01_133525_add_unique_import_identifier_to_family_funding_allocations_table', 'batch' => DB::table('migrations')->max('batch') + 1]
            ]);
        }
        
        // ایجاد جدول benefactors اگر وجود ندارد
        if (!$this->isMigrationRan('2025_06_01_004834_create_benefactors_table')) {
            Schema::create('benefactors', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('mobile')->nullable();
                $table->string('email')->nullable();
                $table->string('national_code', 10)->nullable();
                $table->string('company_name')->nullable();
                $table->string('position')->nullable();
                $table->text('address')->nullable();
                $table->text('description')->nullable();
                $table->decimal('total_donations', 15, 2)->default(0);
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                $table->index('name');
                $table->index('mobile');
                $table->index('email');
                $table->index('national_code');
            });
            
            // علامت‌گذاری مایگریشن به عنوان اجرا شده
            DB::table('migrations')->insert([
                'migration' => '2025_06_01_004834_create_benefactors_table',
                'batch' => DB::table('migrations')->max('batch') + 1
            ]);
        }
        
        // ایجاد جدول family_documents اگر وجود ندارد
        if (!$this->isMigrationRan('2025_06_01_011811_create_family_documents_table')) {
            Schema::create('family_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('family_id')->constrained('families')->onDelete('cascade');
                $table->string('title');
                $table->string('file_path');
                $table->string('file_type');
                $table->string('file_size');
                $table->enum('document_type', ['identity', 'medical', 'financial', 'educational', 'other'])->default('other');
                $table->text('description')->nullable();
                $table->foreignId('uploaded_by')->constrained('users');
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users');
                $table->timestamps();
                
                $table->index(['family_id', 'document_type']);
            });
            
            // علامت‌گذاری مایگریشن به عنوان اجرا شده
            DB::table('migrations')->insert([
                'migration' => '2025_06_01_011811_create_family_documents_table',
                'batch' => DB::table('migrations')->max('batch') + 1
            ]);
        }
        
        // ایجاد جدول committee_decisions اگر وجود ندارد
        if (!$this->isMigrationRan('2025_06_01_013504_create_committee_decisions_table')) {
            Schema::create('committee_decisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('family_id')->constrained('families')->onDelete('cascade');
                $table->string('meeting_code');
                $table->date('meeting_date');
                $table->enum('decision', ['approve', 'reject', 'pending', 'more_info']);
                $table->text('decision_details')->nullable();
                $table->decimal('approved_amount', 15, 2)->nullable();
                $table->json('committee_members')->nullable();
                $table->foreignId('recorded_by')->constrained('users');
                $table->timestamps();
                
                $table->index(['family_id', 'meeting_date']);
                $table->index('meeting_code');
            });
            
            // علامت‌گذاری مایگریشن به عنوان اجرا شده
            DB::table('migrations')->insert([
                'migration' => '2025_06_01_013504_create_committee_decisions_table',
                'batch' => DB::table('migrations')->max('batch') + 1
            ]);
        }
    }

    /**
     * برگرداندن تغییرات انجام شده در مایگریشن
     */
    public function down(): void
    {
        // حذف جداول ایجاد شده
        Schema::dropIfExists('committee_decisions');
        Schema::dropIfExists('family_documents');
        Schema::dropIfExists('benefactors');
        Schema::dropIfExists('family_criteria');
        
        // حذف رکوردهای اضافه شده به جدول migrations
        DB::table('migrations')->whereIn('migration', [
            '2025_06_01_201242_add_username_to_users_table',
            '2024_01_16_000001_create_insurance_shares_table',
            '2025_05_29_101546_create_family_criteria_table',
            '2025_06_01_004828_create_family_funding_allocations_table',
            '2025_06_01_122838_add_transaction_id_to_family_funding_allocations_table',
            '2025_06_01_130534_add_import_log_id_to_family_funding_allocations_table',
            '2025_06_01_133348_add_unique_constraint_to_family_funding_allocations_table',
            '2025_06_01_133525_add_unique_import_identifier_to_family_funding_allocations_table',
            '2025_06_01_004834_create_benefactors_table',
            '2025_06_01_011811_create_family_documents_table',
            '2025_06_01_013504_create_committee_decisions_table'
        ])->delete();
    }
    
    /**
     * بررسی اینکه آیا مایگریشن قبلاً اجرا شده است
     */
    private function isMigrationRan($migration)
    {
        return DB::table('migrations')->where('migration', $migration)->exists();
    }
};
