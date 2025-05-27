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
            // اضافه کردن ستون education اگر وجود نداشته باشد
            if (!Schema::hasColumn('members', 'education')) {
                $table->enum('education', [
                    'illiterate', 'primary', 'middle', 'high_school', 
                    'associate', 'bachelor', 'master', 'phd'
                ])->nullable()->after('marital_status');
            }
            
            // اضافه کردن ستون marital_status اگر وجود نداشته باشد
            if (!Schema::hasColumn('members', 'marital_status')) {
                $table->enum('marital_status', [
                    'single', 'married', 'divorced', 'widowed'
                ])->nullable()->after('gender');
            }
            
            // اضافه کردن ستون father_name اگر وجود نداشته باشد
            if (!Schema::hasColumn('members', 'father_name')) {
                $table->string('father_name')->nullable()->after('national_code');
            }
            
            // اضافه کردن ستون phone اگر وجود نداشته باشد
            if (!Schema::hasColumn('members', 'phone')) {
                $table->string('phone', 20)->nullable()->after('mobile');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'education')) {
                $table->dropColumn('education');
            }
            
            if (Schema::hasColumn('members', 'marital_status')) {
                $table->dropColumn('marital_status');
            }
            
            if (Schema::hasColumn('members', 'father_name')) {
                $table->dropColumn('father_name');
            }
            
            if (Schema::hasColumn('members', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};
