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
        Schema::create('family_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('from_status')->nullable()->comment('وضعیت قبلی خانواده');
            $table->string('to_status')->comment('وضعیت جدید خانواده');
            $table->text('comments')->nullable()->comment('توضیحات تغییر وضعیت');
            $table->json('extra_data')->nullable()->comment('اطلاعات اضافی به صورت JSON');
            $table->foreignId('excel_file_id')->nullable()->comment('اگر تغییر وضعیت با فایل اکسل انجام شده باشد');
            $table->foreignId('insurance_share_id')->nullable()->comment('اگر تغییر وضعیت با سهم‌بندی مرتبط باشد');
            $table->string('batch_id')->nullable()->comment('شناسه دسته‌ای برای گروه‌بندی تغییرات همزمان');
            $table->timestamps();
            
            // ایندکس‌ها برای بهبود عملکرد جستجو
            $table->index(['family_id', 'created_at']);
            $table->index('batch_id');
            $table->index(['from_status', 'to_status']);
        });
        
        Schema::table('families', function (Blueprint $table) {
            $table->string('wizard_status')->nullable()->after('status')->index();
            $table->timestamp('wizard_completed_at')->nullable()->after('wizard_status');
            $table->json('last_step_at')->nullable()->after('wizard_completed_at');
        });
        
        Schema::table('insurance_shares', function (Blueprint $table) {
            if (!Schema::hasColumn('insurance_shares', 'batch_identifier')) {
                $table->string('batch_identifier')->nullable()->after('amount')->index();
            }
            if (!Schema::hasColumn('insurance_shares', 'insurance_batch_id')) {
                $table->foreignId('insurance_batch_id')->nullable()->after('batch_identifier');
            }
            if (!Schema::hasColumn('insurance_shares', 'excel_row_identifier')) {
                $table->string('excel_row_identifier')->nullable()->after('insurance_batch_id');
            }
            if (!Schema::hasColumn('insurance_shares', 'share_meta')) {
                $table->json('share_meta')->nullable()->after('excel_row_identifier');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('insurance_shares', function (Blueprint $table) {
            if (Schema::hasColumn('insurance_shares', 'batch_identifier')) {
                $table->dropColumn('batch_identifier');
            }
            if (Schema::hasColumn('insurance_shares', 'insurance_batch_id')) {
                $table->dropColumn('insurance_batch_id');
            }
            if (Schema::hasColumn('insurance_shares', 'excel_row_identifier')) {
                $table->dropColumn('excel_row_identifier');
            }
            if (Schema::hasColumn('insurance_shares', 'share_meta')) {
                $table->dropColumn('share_meta');
            }
        });
        
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn(['wizard_status', 'wizard_completed_at', 'last_step_at']);
        });
        
        Schema::dropIfExists('family_status_logs');
    }
};
