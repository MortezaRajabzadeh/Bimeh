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
        Schema::create('benefactors', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // نام نیکوکار
            $table->string('phone')->nullable(); // شماره تماس
            $table->string('email')->nullable(); // ایمیل
            $table->decimal('total_contributed', 12, 2)->default(0); // مجموع مشارکت
            $table->boolean('is_active')->default(true); // وضعیت فعال بودن
            $table->text('notes')->nullable(); // یادداشت‌های اضافی
            $table->timestamps();

            // Indexes
            $table->index('is_active');
            $table->index('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benefactors');
    }
};
