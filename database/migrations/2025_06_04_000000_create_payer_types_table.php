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
        if (!Schema::hasTable('payer_types')) {
            Schema::create('payer_types', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('نام نوع پرداخت‌کننده');
                $table->string('slug')->unique()->comment('شناسه یکتا');
                $table->text('description')->nullable()->comment('توضیحات');
                $table->boolean('is_active')->default(true)->comment('فعال/غیرفعال');
                $table->timestamps();
                
                $table->index('is_active');
            });

            // Create default payer types manually
            $this->createDefaultPayerTypes();
        }
    }
    
    private function createDefaultPayerTypes()
    {
        
        $defaultTypes = [
            ['name' => 'شرکت بیمه', 'slug' => 'insurance_company', 'description' => 'پرداخت توسط شرکت بیمه'],
            ['name' => 'خیریه', 'slug' => 'charity', 'description' => 'پرداخت توسط خیریه'],
            ['name' => 'بانک', 'slug' => 'bank', 'description' => 'پرداخت توسط بانک'],
            ['name' => 'دولت', 'slug' => 'government', 'description' => 'پرداخت دولتی'],
            ['name' => 'نیکوکار فردی', 'slug' => 'individual_donor', 'description' => 'پرداخت توسط نیکوکار فردی'],
            ['name' => 'بودجه CSR', 'slug' => 'csr_budget', 'description' => 'پرداخت از بودجه مسئولیت اجتماعی شرکت'],
            ['name' => 'سایر', 'slug' => 'other', 'description' => 'سایر منابع پرداخت']
        ];
        
        foreach ($defaultTypes as $type) {
            \App\Models\PayerType::firstOrCreate(
                ['slug' => $type['slug']],
                $type + ['is_active' => true, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payer_types');
    }
}; 