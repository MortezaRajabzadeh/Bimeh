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
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('family_code')->unique();
            $table->unsignedBigInteger('province_id')->nullable()->index();
            $table->unsignedBigInteger('city_id')->nullable()->index();
            $table->unsignedBigInteger('district_id')->nullable()->index();
            $table->unsignedBigInteger('region_id')->nullable()->index();
            $table->foreignId('charity_id')->nullable()->constrained('organizations')->onDelete('set null');
            $table->foreignId('insurance_id')->nullable()->constrained('organizations')->onDelete('set null');
            $table->foreignId('registered_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->enum('housing_status', ['owner', 'tenant', 'relative', 'other'])->nullable();
            $table->text('housing_description')->nullable();
            $table->enum('status', ['pending', 'reviewing', 'approved', 'insured', 'renewal', 'rejected', 'deleted'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->boolean('poverty_confirmed')->default(false);
            $table->boolean('is_insured')->default(false);
            $table->decimal('insurance_amount', 18, 2)->nullable();
            $table->date('insurance_start_date')->nullable();
            $table->date('insurance_end_date')->nullable();
            $table->date('insurance_issue_date')->nullable();
            $table->text('additional_info')->nullable();
            $table->json('acceptance_criteria')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('families');
    }
}; 