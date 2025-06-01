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
        Schema::create('family_funding_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families');
            $table->foreignId('funding_source_id')->constrained('funding_sources');
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('percentage', 8, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('path')->nullable()->comment('مسیر فایل مستندات مربوط به تخصیص بودجه');
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid', 'expired'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('paid_at')->nullable();
            $table->string('transaction_id')->nullable()->unique();
            $table->foreignId('import_log_id')->nullable();
            $table->string('unique_import_identifier')->nullable()->unique();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
            
            // ایجاد یک کلید منحصر به فرد برای جلوگیری از تخصیص تکراری
            $table->unique(['family_id', 'funding_source_id', 'percentage'], 'unique_family_funding');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_funding_allocations');
    }
};
