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
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('family_code')->unique()->comment('کد خانوار');
            $table->string('region')->nullable()->comment('نام منطقه');
            $table->foreignId('charity_id')->nullable()->comment('سازمان خیریه ثبت‌کننده')
                ->constrained('organizations')->onDelete('set null');
            $table->foreignId('insurance_id')->nullable()->comment('سازمان بیمه تأییدکننده')
                ->constrained('organizations')->onDelete('set null');
            $table->foreignId('registered_by')->nullable()->comment('کاربر ثبت‌کننده')
                ->constrained('users')->onDelete('set null');
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->enum('housing_status', ['owner', 'tenant', 'relative', 'other'])->nullable()
                ->comment('وضعیت مسکن: مالک، مستأجر، اقوام، سایر');
            $table->text('housing_description')->nullable()->comment('توضیحات تکمیلی مسکن');
            $table->enum('status', ['pending', 'reviewing', 'approved', 'rejected'])
                ->default('pending')->comment('وضعیت بررسی');
            $table->text('rejection_reason')->nullable()->comment('دلیل رد درخواست');
            $table->boolean('poverty_confirmed')->default(false)->comment('تأیید شرایط کم‌برخورداری');
            $table->boolean('is_insured')->default(false)->comment('وضعیت بیمه خانواده');
            $table->text('additional_info')->nullable()->comment('اطلاعات تکمیلی');
            $table->timestamp('verified_at')->nullable()->comment('زمان تأیید');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('families');
    }
}; 