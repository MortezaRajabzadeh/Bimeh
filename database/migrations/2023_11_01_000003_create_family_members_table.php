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
        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_code', 10)->unique();
            $table->string('father_name');
            $table->string('birth_date');
            $table->enum('gender', ['male', 'female']);
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->string('education')->nullable();
            $table->string('occupation')->nullable();
            $table->string('mobile')->nullable()->unique();
            $table->string('relationship')->nullable();
            $table->boolean('is_head')->default(false);
            $table->boolean('has_disability')->default(false);
            $table->boolean('has_chronic_disease')->default(false);
            $table->boolean('has_insurance')->default(false);
            $table->string('insurance_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_members');
    }
}; 