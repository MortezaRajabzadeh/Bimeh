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
        // جدول پرداخت‌های بیمه
        Schema::create('insurance_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_code')->unique()->comment('کد پرداخت');
            $table->foreignId('family_insurance_id')->constrained('family_insurances')->onDelete('cascade');
            $table->decimal('total_amount', 15, 2)->comment('مبلغ کل پرداخت');
            $table->integer('insured_persons_count')->comment('تعداد افراد بیمه‌شده');
            $table->date('payment_date')->comment('تاریخ پرداخت');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable()->comment('روش پرداخت');
            $table->string('transaction_reference')->nullable()->comment('شماره مرجع تراکنش');
            $table->text('description')->nullable();
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['payment_status', 'payment_date']);
            $table->index(['family_insurance_id', 'payment_status']);
        });

        // جدول جزئیات پرداخت برای هر فرد
        Schema::create('insurance_payment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_payment_id')->constrained('insurance_payments')->onDelete('cascade');
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
            $table->decimal('individual_amount', 15, 2)->comment('مبلغ تعلق گرفته به این فرد');
            $table->string('insurance_type')->comment('نوع بیمه این فرد');
            $table->date('coverage_start_date')->nullable()->comment('تاریخ شروع پوشش');
            $table->date('coverage_end_date')->nullable()->comment('تاریخ پایان پوشش');
            $table->text('notes')->nullable()->comment('یادداشت‌های اضافی');
            $table->timestamps();
            
            $table->index(['insurance_payment_id', 'member_id']);
            $table->unique(['insurance_payment_id', 'member_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_payment_details');
        Schema::dropIfExists('insurance_payments');
    }
};
