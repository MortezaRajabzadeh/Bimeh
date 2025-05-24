<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('insurance_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->nullable(); // نام فایل آپلود شده
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // شناسه کاربر انجام دهنده عملیات
            $table->integer('total_rows')->default(0);        // تعداد کل ردیف‌های فایل
            $table->integer('created_count')->default(0);     // تعداد خانواده‌های جدید
            $table->integer('updated_count')->default(0);     // تعداد خانواده‌های آپدیت شده
            $table->integer('skipped_count')->default(0);     // تعداد خانواده‌های بدون تغییر
            $table->integer('error_count')->default(0);       // تعداد خطاها
            $table->decimal('total_insurance_amount', 15, 2)->default(0); // مجموع مبلغ بیمه‌ها
            $table->json('family_codes')->nullable();         // شناسه‌های خانواده‌های پردازش شده (می‌تواند آرایه باشد)
            $table->json('updated_family_codes')->nullable(); // شناسه‌های خانواده‌های آپدیت شده
            $table->json('created_family_codes')->nullable(); // شناسه‌های خانواده‌های ایجاد شده
            $table->text('errors')->nullable();               // لیست خطاها
            $table->timestamps();                             // created_at و updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('insurance_import_logs');
    }
};