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
        Schema::create('family_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->onDelete('cascade')
                  ->comment('شناسه خانواده');
            $table->foreignId('rank_setting_id')->constrained('rank_settings')->onDelete('cascade')
                  ->comment('شناسه معیار رتبه‌بندی');
            $table->boolean('has_criteria')->default(true)->comment('آیا این معیار در خانواده وجود دارد');
            $table->text('notes')->nullable()->comment('یادداشت‌های اضافی');
            $table->timestamps();
            
            $table->unique(['family_id', 'rank_setting_id'], 'family_criteria_unique');
            $table->index(['family_id', 'has_criteria']);
        });
        
        // اضافه کردن فیلد محاسبه رتبه به جدول families
        Schema::table('families', function (Blueprint $table) {
            $table->integer('calculated_rank')->nullable()->after('acceptance_criteria')
                  ->comment('رتبه محاسبه شده بر اساس معیارها');
            $table->timestamp('rank_calculated_at')->nullable()->after('calculated_rank')
                  ->comment('زمان آخرین محاسبه رتبه');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn(['calculated_rank', 'rank_calculated_at']);
        });
        
        Schema::dropIfExists('family_criteria');
    }
};
