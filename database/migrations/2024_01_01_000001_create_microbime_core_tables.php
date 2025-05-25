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
        // جدول استان‌ها
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('deprivation_rank')->default(5)->comment('رتبه محرومیت استان (1-10)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['is_active', 'deprivation_rank']);
        });

        // جدول شهرها
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('province_id')->constrained('provinces')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['province_id', 'is_active']);
        });

        // جدول دهستان‌ها
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('city_id')->constrained('cities')->onDelete('cascade');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['city_id', 'is_active']);
        });

        // جدول سازمان‌ها (خیریه‌ها و بیمه‌ها)
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['charity', 'insurance'])->comment('نوع سازمان: خیریه یا بیمه');
            $table->string('code')->nullable()->comment('کد اختصاصی سازمان');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('logo_path')->nullable()->comment('مسیر لوگوی سازمان');
            $table->text('description')->nullable()->comment('توضیحات اضافی');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['type', 'is_active']);
        });

        // جدول مناطق
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique()->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('is_active');
        });

        // جدول خانواده‌ها
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('family_code')->unique();
            $table->foreignId('province_id')->nullable()->constrained('provinces')->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained('cities')->onDelete('set null');
            $table->foreignId('district_id')->nullable()->constrained('districts')->onDelete('set null');
            $table->foreignId('region_id')->nullable()->constrained('regions')->onDelete('set null');
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
            $table->text('additional_info')->nullable();
            $table->json('acceptance_criteria')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'verified_at']);
            $table->index(['charity_id', 'status']);
            $table->index(['province_id', 'city_id']);
        });

        // جدول اعضای خانواده
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('national_code', 10)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('relationship')->nullable()->comment('نسبت با سرپرست خانوار');
            $table->string('relationship_fa')->nullable()->comment('نسبت فارسی');
            $table->boolean('is_head')->default(false)->comment('آیا سرپرست خانوار است؟');
            $table->string('mobile', 11)->nullable();
            $table->string('occupation')->nullable();
            $table->boolean('has_disability')->default(false);
            $table->boolean('has_chronic_disease')->default(false);
            $table->boolean('has_insurance')->default(false);
            $table->string('insurance_type')->nullable();
            $table->date('insurance_start_date')->nullable();
            $table->date('insurance_end_date')->nullable();
            $table->string('sheba')->nullable()->comment('شماره شبا برای پرداخت خسارت');
            $table->foreignId('charity_id')->nullable()->constrained('organizations')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['family_id', 'is_head']);
            $table->index(['has_insurance', 'insurance_type']);
            $table->index('national_code');
        });

        // جدول بیمه خانواده‌ها
        Schema::create('family_insurances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->onDelete('cascade');
            $table->string('insurance_type')->comment('نوع بیمه');
            $table->string('insurance_payer')->nullable()->comment('پرداخت کننده حق بیمه');
            $table->decimal('premium_amount', 15, 2)->nullable()->comment('مبلغ حق بیمه');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamps();
            
            $table->index(['family_id', 'insurance_type']);
        });

        // جدول منابع تأمین مالی
        Schema::create('funding_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('نام منبع تأمین مالی');
            $table->text('description')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جدول تراکنش‌های مالی
        Schema::create('funding_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('funding_source_id')->constrained('funding_sources')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['income', 'expense'])->comment('نوع تراکنش: درآمد یا هزینه');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // جدول تخصیص بیمه
        Schema::create('insurance_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained('families')->onDelete('cascade');
            $table->foreignId('funding_source_id')->constrained('funding_sources')->onDelete('cascade');
            $table->decimal('allocated_amount', 15, 2)->comment('مبلغ تخصیص یافته');
            $table->date('allocation_date')->comment('تاریخ تخصیص');
            $table->enum('status', ['pending', 'approved', 'paid'])->default('pending');
            $table->timestamps();
        });

        // جدول لاگ ورودی بیمه
        Schema::create('insurance_import_logs', function (Blueprint $table) {
            $table->id();
            $table->string('file_name')->comment('نام فایل وارد شده');
            $table->integer('total_records')->comment('تعداد کل رکوردها');
            $table->integer('successful_records')->default(0)->comment('تعداد رکوردهای موفق');
            $table->integer('failed_records')->default(0)->comment('تعداد رکوردهای ناموفق');
            $table->json('errors')->nullable()->comment('خطاهای رخ داده');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('insurance_import_logs');
        Schema::dropIfExists('insurance_allocations');
        Schema::dropIfExists('funding_transactions');
        Schema::dropIfExists('funding_sources');
        Schema::dropIfExists('family_insurances');
        Schema::dropIfExists('members');
        Schema::dropIfExists('families');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('organizations');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
    }
}; 