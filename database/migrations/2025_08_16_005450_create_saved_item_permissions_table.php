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
        Schema::create('saved_item_permissions', function (Blueprint $table) {
            $table->id();
            $table->morphs('item'); // نوع آیتم (saved_filter یا saved_rank_setting)
            $table->unsignedBigInteger('user_id'); // کاربری که مجوز دارد
            $table->enum('permission_type', ['view', 'edit', 'delete', 'share']); // نوع مجوز
            $table->unsignedBigInteger('granted_by'); // چه کسی مجوز داده
            $table->timestamp('expires_at')->nullable(); // تاریخ انقضاء (اختیاری)
            $table->timestamps();
            
            // Indexes
            $table->index(['item_type', 'item_id', 'user_id']);
            $table->index(['user_id', 'permission_type']);
            $table->index('expires_at');
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('granted_by')->references('id')->on('users')->onDelete('cascade');
            
            // Unique constraint - هر کاربر فقط یک نوع مجوز برای هر آیتم
            $table->unique(['item_type', 'item_id', 'user_id', 'permission_type'], 'unique_item_user_permission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_item_permissions');
    }
};
