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
        Schema::create('rank_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('نام المان موثر در رتبه‌بندی');
            $table->string('key')->unique()->comment('کلید یکتا المان (برای کد)');
            $table->text('description')->nullable()->comment('توضیحات المان');
            $table->integer('weight')->default(1)->comment('وزن عددی المان در محاسبه رتبه');
            $table->enum('category', ['disability', 'disease', 'addiction', 'economic', 'social', 'other'])
                  ->default('other')->comment('دسته‌بندی معیار');
            $table->boolean('is_active')->default(true)->comment('فعال/غیرفعال');
            $table->integer('sort_order')->default(0)->comment('ترتیب نمایش');
            $table->timestamps();
            
            $table->index(['is_active', 'category']);
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rank_settings');
    }
};
