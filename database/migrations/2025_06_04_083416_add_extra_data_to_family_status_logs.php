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
        Schema::table('family_status_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('family_status_logs', 'extra_data')) {
                $table->json('extra_data')->nullable()->after('comments')->comment('اطلاعات اضافی به صورت JSON');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_status_logs', function (Blueprint $table) {
            if (Schema::hasColumn('family_status_logs', 'extra_data')) {
                $table->dropColumn('extra_data');
            }
        });
    }
};
