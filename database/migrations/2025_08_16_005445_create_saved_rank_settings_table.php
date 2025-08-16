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
        Schema::create('saved_rank_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200); // نام تنظیمات رتبه
            $table->text('description')->nullable(); // توضیحات
            $table->unsignedBigInteger('user_id'); // ایجادکننده
            $table->unsignedBigInteger('organization_id')->nullable(); // سازمان (اختیاری)
            $table->json('rank_config'); // تنظیمات رتبه‌بندی (JSON)
            $table->enum('visibility', ['private', 'organization', 'public'])->default('private'); // نوع دسترسی
            $table->boolean('is_active')->default(true); // فعال/غیرفعال
            $table->integer('usage_count')->default(0); // تعداد استفاده
            $table->timestamp('last_used_at')->nullable(); // آخرین استفاده
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'visibility']);
            $table->index(['organization_id', 'visibility']);
            $table->index(['visibility', 'is_active']);
            $table->index('last_used_at');
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_rank_settings');
    }
};
