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
        Schema::table('funding_sources', function (Blueprint $table) {
            // بررسی ستون‌های موجود و اضافه کردن در صورت عدم وجود
            if (!Schema::hasColumn('funding_sources', 'annual_budget')) {
                $table->decimal('annual_budget', 15, 2)->default(0)->after('description'); // بودجه سالانه
            }
            
            if (!Schema::hasColumn('funding_sources', 'allocated_amount')) {
                $table->decimal('allocated_amount', 15, 2)->default(0)->after('annual_budget'); // مبلغ تخصیص داده شده
            }
            
            if (!Schema::hasColumn('funding_sources', 'remaining_amount')) {
                $table->decimal('remaining_amount', 15, 2)->default(0)->after('allocated_amount'); // مبلغ باقیمانده
            }
            
            if (!Schema::hasColumn('funding_sources', 'source_type')) {
                $table->enum('source_type', ['csr', 'bank', 'government', 'benefactor', 'other'])->default('other')->after('type'); // نوع منبع
            }
            
            if (!Schema::hasColumn('funding_sources', 'benefactor_id')) {
                $table->unsignedBigInteger('benefactor_id')->nullable()->after('source_type'); // شناسه نیکوکار (اگر منبع از نیکوکاران باشد)
            }
            
            if (!Schema::hasColumn('funding_sources', 'contact_info')) {
                $table->text('contact_info')->nullable()->after('benefactor_id'); // اطلاعات تماس
            }
            
            // بررسی وجود کلید خارجی
            if (!Schema::hasTable('benefactors')) {
                // ایجاد جدول benefactors
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
            }
            
            // Foreign key برای نیکوکار اگر وجود نداشته باشد
            $foreignKeys = DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'funding_sources' AND COLUMN_NAME = 'benefactor_id' AND CONSTRAINT_NAME = 'funding_sources_benefactor_id_foreign'");
            if (empty($foreignKeys)) {
                $table->foreign('benefactor_id')->references('id')->on('benefactors')->onDelete('set null');
            }
            
            // Indexes اگر وجود نداشته باشند
            $indexes = DB::select("SHOW INDEX FROM funding_sources WHERE Column_name = 'source_type'");
            if (empty($indexes)) {
                $table->index('source_type');
            }
            
            $indexes = DB::select("SHOW INDEX FROM funding_sources WHERE Column_name = 'benefactor_id'");
            if (empty($indexes)) {
                $table->index('benefactor_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funding_sources', function (Blueprint $table) {
            // حذف کلید خارجی اگر وجود داشته باشد
            $foreignKeys = DB::select("SELECT * FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'funding_sources' AND COLUMN_NAME = 'benefactor_id' AND CONSTRAINT_NAME = 'funding_sources_benefactor_id_foreign'");
            if (!empty($foreignKeys)) {
                $table->dropForeign(['benefactor_id']);
            }
            
            // حذف ستون‌ها اگر وجود داشته باشند
            $columns = ['annual_budget', 'allocated_amount', 'remaining_amount', 'source_type', 'benefactor_id', 'contact_info'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('funding_sources', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
