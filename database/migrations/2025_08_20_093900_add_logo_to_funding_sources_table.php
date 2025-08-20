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
        Schema::table('funding_sources', function (Blueprint $table) {
            // اضافه کردن فیلد لوگو
            if (!Schema::hasColumn('funding_sources', 'logo')) {
                $table->string('logo')->nullable()->after('name')->comment('مسیر لوگو منبع تأمین مالی');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('funding_sources', function (Blueprint $table) {
            if (Schema::hasColumn('funding_sources', 'logo')) {
                $table->dropColumn('logo');
            }
        });
    }
};
