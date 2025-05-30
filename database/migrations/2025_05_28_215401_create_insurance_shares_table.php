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
            $table->foreignId('family_insurance_id')->constrained('family_insurances')->onDelete('cascade');
            $table->decimal('percentage', 5, 2)->comment('درصد مشارکت (0.00 تا 100.00)');
            $table->enum('payer_type', [
                'insurance_company', 
                'charity', 
                'bank', 
                'government', 
                'individual_donor',
                'csr_budget',
                'other'
            ])->comment('نوع پرداخت‌کننده');
            $table->string('payer_name')->comment('نام پرداخت‌کننده');
            $table->foreignId('payer_organization_id')->nullable()->constrained('organizations')->onDelete('set null');
            $table->foreignId('payer_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('amount', 15, 2)->nullable()->comment('مبلغ محاسبه شده بر اساس درصد');
            $table->text('description')->nullable()->comment('توضیحات اضافی');
            $table->boolean('is_paid')->default(false)->comment('آیا پرداخت شده است؟');
            $table->date('payment_date')->nullable()->comment('تاریخ پرداخت');
            $table->string('payment_reference')->nullable()->comment('شماره مرجع پرداخت');
            $table->timestamps();
            
            $table->index(['family_insurance_id', 'payer_type']);
            $table->index(['is_paid', 'payment_date']);
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
