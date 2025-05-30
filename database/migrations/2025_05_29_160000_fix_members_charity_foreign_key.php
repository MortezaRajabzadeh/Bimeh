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
            // حذف foreign key constraint قدیمی
            $table->dropForeign(['charity_id']);
            
            // اضافه کردن foreign key constraint جدید به جدول organizations
            $table->foreign('charity_id')->references('id')->on('organizations')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            // حذف foreign key constraint جدید
            $table->dropForeign(['charity_id']);
            
            // بازگرداندن foreign key constraint قدیمی
            $table->foreign('charity_id')->references('id')->on('charities')->onDelete('set null');
        });
    }
}; 