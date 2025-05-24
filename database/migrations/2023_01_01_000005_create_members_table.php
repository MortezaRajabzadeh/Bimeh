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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_code', 10)->unique()->comment('کد ملی');
            $table->string('father_name')->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->enum('education', ['illiterate', 'primary', 'secondary', 'diploma', 'associate', 'bachelor', 'master', 'doctorate'])->nullable()
                ->comment('تحصیلات');
            $table->enum('relationship', ['head', 'spouse', 'child', 'parent', 'other'])->comment('نسبت: سرپرست، همسر، فرزند، والدین، سایر');
            $table->boolean('is_head')->default(false)->comment('سرپرست خانوار');
            $table->boolean('has_disability')->default(false)->comment('معلولیت');
            $table->boolean('has_chronic_disease')->default(false)->comment('بیماری مزمن');
            $table->boolean('has_insurance')->default(false)->comment('دارای بیمه'); 
            $table->string('insurance_type')->nullable()->comment('نوع بیمه');
            $table->text('special_conditions')->nullable()->comment('شرایط خاص');
            $table->string('occupation')->nullable()->comment('شغل');
            $table->boolean('is_employed')->default(false)->comment('شاغل');
            $table->json('problem_type')->nullable();
            $table->string('mobile')->nullable();
            $table->string('phone')->nullable();
            $table->string('sheba', 30)->nullable()->comment('شماره شبا');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
}; 