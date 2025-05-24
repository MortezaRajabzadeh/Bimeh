<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('funding_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funding_source_id')->constrained();
            $table->bigInteger('amount');
            $table->string('description')->nullable();
            $table->string('reference_no')->nullable();
            $table->bigInteger('allocated')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('funding_transactions');
    }
}; 