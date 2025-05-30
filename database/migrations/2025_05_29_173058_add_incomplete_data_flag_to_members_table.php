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
        Schema::table('members', function (Blueprint $table) {
            // فلگ اطلاعات ناقص
            $table->boolean('has_incomplete_data')->default(false)->after('sheba');
            
            // جزئیات اطلاعات ناقص (JSON)
            $table->json('incomplete_data_details')->nullable()->after('has_incomplete_data');
            
            // تاریخ آخرین بروزرسانی اطلاعات ناقص
            $table->timestamp('incomplete_data_updated_at')->nullable()->after('incomplete_data_details');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['has_incomplete_data', 'incomplete_data_details', 'incomplete_data_updated_at']);
        });
    }
};
