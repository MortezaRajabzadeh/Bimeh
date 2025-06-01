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
        Schema::create('insurance_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->onDelete('cascade');
            $table->foreignId('funding_source_id')->constrained('funding_sources')->onDelete('cascade');
            $table->decimal('percentage', 5, 2)->comment('درصد سهم‌بندی (باید بین 0 تا 100 باشد)');
            $table->decimal('amount', 15, 2)->nullable()->comment('مبلغ محاسبه شده بر اساس درصد');
            $table->text('description')->nullable()->comment('توضیحات');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('import_log_id')->nullable()->constrained('insurance_import_logs')->onDelete('set null');
            $table->timestamps();

            // ایندکس مرکب برای جلوگیری از تخصیص مضاعف
            $table->unique(['family_id', 'funding_source_id', 'percentage'], 'unique_family_source_percentage');
            $table->index(['import_log_id', 'funding_source_id', 'percentage'], 'idx_import_source_percentage');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_shares');
    }
}; 