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
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->after('id');
            $table->string('mobile')->nullable()->after('email');
            $table->foreignId('organization_id')->nullable()->after('mobile')
                ->constrained()->onDelete('set null');
            $table->enum('user_type', ['admin', 'charity', 'insurance'])->default('charity')
                ->after('remember_token')->comment('نوع کاربر: ادمین، خیریه، بیمه');
            $table->boolean('is_active')->default(true)->after('user_type');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn([
                'username',
                'mobile',
                'organization_id',
                'user_type',
                'is_active'
            ]);
            $table->dropSoftDeletes();
        });
    }
}; 