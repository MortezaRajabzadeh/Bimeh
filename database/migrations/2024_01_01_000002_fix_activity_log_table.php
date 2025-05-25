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
        // بررسی اینکه آیا جدول activity_log وجود دارد
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                // اضافه کردن ستون event اگر وجود نداشته باشد
                if (!Schema::hasColumn('activity_log', 'event')) {
                    $table->string('event')->nullable()->after('description');
                }
                
                // اضافه کردن ستون batch_uuid اگر وجود نداشته باشد
                if (!Schema::hasColumn('activity_log', 'batch_uuid')) {
                    $table->uuid('batch_uuid')->nullable()->after('properties');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('activity_log')) {
            Schema::table('activity_log', function (Blueprint $table) {
                if (Schema::hasColumn('activity_log', 'event')) {
                    $table->dropColumn('event');
                }
                
                if (Schema::hasColumn('activity_log', 'batch_uuid')) {
                    $table->dropColumn('batch_uuid');
                }
            });
        }
    }
}; 