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
        Schema::table('family_insurances', function (Blueprint $table) {
            $table->string('family_code')->nullable()->after('family_id')->comment('کد خانواده (اختیاری)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('family_insurances', function (Blueprint $table) {
            $table->dropColumn('family_code');
        });
    }
};
