<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
// In new migration file for share_allocation_logs
public function up(): void
{
    Schema::create('share_allocation_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
        $table->string('batch_id')->unique(); // یک شناسه منحصر به فرد برای هر عملیات گروهی
        $table->text('description')->nullable(); // مثلا: "تخصیص سهم ۱۰۰٪ از منبع مالی بانک"
        $table->integer('families_count')->default(0); // تعداد خانواده‌ها
        $table->json('family_ids'); // لیست ID خانواده‌ها
        $table->json('shares_data'); // اطلاعات سهم‌های تخصیص داده شده
        $table->decimal('total_amount', 15, 2)->default(0); // مبلغ کل که بعد از آپلود اکسل آپدیت می‌شود
        $table->string('status')->default('pending_excel_upload'); // وضعیت این عملیات
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('share_allocation_logs');
    }
};
