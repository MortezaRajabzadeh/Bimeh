<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('insurance_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funding_transaction_id')->constrained();
            $table->foreignId('family_id')->constrained();
            $table->bigInteger('amount');
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('insurance_allocations');
    }
}; 