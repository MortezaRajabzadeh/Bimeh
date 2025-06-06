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
        Schema::create('payer_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('نام نوع پرداخت‌کننده');
            $table->string('slug')->unique()->comment('شناسه یکتا');
            $table->text('description')->nullable()->comment('توضیحات');
            $table->boolean('is_active')->default(true)->comment('فعال/غیرفعال');
            $table->timestamps();
            
            $table->index('is_active');
        });

        // Create default payer types
        \App\Models\PayerType::createDefaults();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payer_types');
    }
}; 