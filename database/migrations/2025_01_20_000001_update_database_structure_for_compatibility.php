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
        // 1. آپدیت جدول insurance_import_logs
        if (Schema::hasTable('insurance_import_logs')) {
            Schema::table('insurance_import_logs', function (Blueprint $table) {
                if (!Schema::hasColumn('insurance_import_logs', 'user_id')) {
                    $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'total_rows')) {
                    $table->integer('total_rows')->default(0)->after('file_name');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'created_count')) {
                    $table->integer('created_count')->default(0)->after('total_rows');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'updated_count')) {
                    $table->integer('updated_count')->default(0)->after('created_count');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'skipped_count')) {
                    $table->integer('skipped_count')->default(0)->after('updated_count');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'error_count')) {
                    $table->integer('error_count')->default(0)->after('skipped_count');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'total_insurance_amount')) {
                    $table->decimal('total_insurance_amount', 15, 2)->default(0)->after('error_count');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'family_codes')) {
                    $table->json('family_codes')->nullable()->after('total_insurance_amount');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'updated_family_codes')) {
                    $table->json('updated_family_codes')->nullable()->after('family_codes');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'created_family_codes')) {
                    $table->json('created_family_codes')->nullable()->after('updated_family_codes');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'family_id')) {
                    $table->foreignId('family_id')->nullable()->constrained('families')->onDelete('set null');
                }
                if (!Schema::hasColumn('insurance_import_logs', 'row_data')) {
                    $table->json('row_data')->nullable();
                }
                if (!Schema::hasColumn('insurance_import_logs', 'status')) {
                    $table->string('status')->nullable();
                }
                if (!Schema::hasColumn('insurance_import_logs', 'message')) {
                    $table->text('message')->nullable();
                }
                
                // تغییر نام ستون errors از json به text
                if (Schema::hasColumn('insurance_import_logs', 'errors')) {
                    $table->text('errors')->nullable()->change();
                }
            });
        }

        // 2. آپدیت جدول families برای اضافه کردن head_id
        if (Schema::hasTable('families') && !Schema::hasColumn('families', 'head_id')) {
            Schema::table('families', function (Blueprint $table) {
                $table->foreignId('head_id')->nullable()->after('region_id')->constrained('members')->onDelete('set null');
                $table->index('head_id');
            });
        }

        // 3. آپدیت جدول members
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                if (!Schema::hasColumn('members', 'father_name')) {
                    $table->string('father_name')->nullable()->after('national_code');
                }
                if (!Schema::hasColumn('members', 'marital_status')) {
                    $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable()->after('gender');
                }
                if (!Schema::hasColumn('members', 'education')) {
                    $table->enum('education', ['illiterate', 'primary', 'middle', 'high_school', 'associate', 'bachelor', 'master', 'phd'])->nullable()->after('marital_status');
                }
                if (!Schema::hasColumn('members', 'is_employed')) {
                    $table->boolean('is_employed')->default(false);
                }
                if (!Schema::hasColumn('members', 'phone')) {
                    $table->string('phone', 20)->nullable()->after('mobile');
                }
                if (!Schema::hasColumn('members', 'special_conditions')) {
                    $table->text('special_conditions')->nullable();
                }
                if (!Schema::hasColumn('members', 'problem_type')) {
                    $table->json('problem_type')->nullable();
                }
            });
        }

        // 4. ایجاد جدول insurance_shares
        if (!Schema::hasTable('insurance_shares')) {
            Schema::create('insurance_shares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('family_insurance_id')->constrained('family_insurances')->onDelete('cascade');
                $table->decimal('percentage', 5, 2)->comment('درصد مشارکت (0.00 تا 100.00)');
                $table->enum('payer_type', [
                    'insurance_company', 
                    'charity', 
                    'bank', 
                    'government', 
                    'individual_donor',
                    'csr_budget',
                    'other'
                ])->comment('نوع پرداخت‌کننده');
                $table->string('payer_name')->comment('نام پرداخت‌کننده');
                $table->foreignId('payer_organization_id')->nullable()->constrained('organizations')->onDelete('set null');
                $table->foreignId('payer_user_id')->nullable()->constrained('users')->onDelete('set null');
                $table->decimal('amount', 15, 2)->nullable()->comment('مبلغ محاسبه شده بر اساس درصد');
                $table->text('description')->nullable()->comment('توضیحات اضافی');
                $table->boolean('is_paid')->default(false)->comment('آیا پرداخت شده است؟');
                $table->date('payment_date')->nullable()->comment('تاریخ پرداخت');
                $table->string('payment_reference')->nullable()->comment('شماره مرجع پرداخت');
                $table->timestamps();
                
                $table->index(['family_insurance_id', 'payer_type']);
                $table->index(['is_paid', 'payment_date']);
            });
        }

        // 5. ایجاد جدول insurance_payments
        if (!Schema::hasTable('insurance_payments')) {
            Schema::create('insurance_payments', function (Blueprint $table) {
                $table->id();
                $table->string('payment_code')->unique()->comment('کد پرداخت');
                $table->foreignId('family_insurance_id')->constrained('family_insurances')->onDelete('cascade');
                $table->decimal('total_amount', 15, 2)->comment('مبلغ کل پرداخت');
                $table->integer('insured_persons_count')->comment('تعداد افراد بیمه‌شده');
                $table->date('payment_date')->comment('تاریخ پرداخت');
                $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
                $table->string('payment_method')->nullable()->comment('روش پرداخت');
                $table->string('transaction_reference')->nullable()->comment('شماره مرجع تراکنش');
                $table->text('description')->nullable();
                $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
                
                $table->index(['payment_status', 'payment_date']);
                $table->index(['family_insurance_id', 'payment_status']);
            });
        }

        // 6. ایجاد جدول insurance_payment_details
        if (!Schema::hasTable('insurance_payment_details')) {
            Schema::create('insurance_payment_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('insurance_payment_id')->constrained('insurance_payments')->onDelete('cascade');
                $table->foreignId('member_id')->constrained('members')->onDelete('cascade');
                $table->decimal('individual_amount', 15, 2)->comment('مبلغ تعلق گرفته به این فرد');
                $table->string('insurance_type')->comment('نوع بیمه این فرد');
                $table->date('coverage_start_date')->nullable()->comment('تاریخ شروع پوشش');
                $table->date('coverage_end_date')->nullable()->comment('تاریخ پایان پوشش');
                $table->text('notes')->nullable()->comment('یادداشت‌های اضافی');
                $table->timestamps();
                
                $table->index(['insurance_payment_id', 'member_id']);
                $table->unique(['insurance_payment_id', 'member_id']);
            });
        }

        // 7. اضافه کردن Index های مفید (بدون بررسی existence)
        try {
            Schema::table('families', function (Blueprint $table) {
                $table->index(['status', 'verified_at'], 'idx_families_status_verified');
                $table->index(['charity_id', 'status'], 'idx_families_charity_status');  
                $table->index(['province_id', 'city_id'], 'idx_families_province_city');
            });
        } catch (\Exception $e) {
            // Index ها ممکن است قبلاً وجود داشته باشند
        }

        try {
            Schema::table('members', function (Blueprint $table) {
                $table->index(['family_id', 'is_head'], 'idx_members_family_head');
                $table->index(['has_insurance', 'insurance_type'], 'idx_members_insurance');
                $table->index('national_code', 'idx_members_national_code');
            });
        } catch (\Exception $e) {
            // Index ها ممکن است قبلاً وجود داشته باشند
        }

        try {
            Schema::table('family_insurances', function (Blueprint $table) {
                $table->index(['family_id', 'insurance_type'], 'idx_family_insurances_family_type');
            });
        } catch (\Exception $e) {
            // Index ها ممکن است قبلاً وجود داشته باشند
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف جداول اضافه شده
        Schema::dropIfExists('insurance_payment_details');
        Schema::dropIfExists('insurance_payments');
        Schema::dropIfExists('insurance_shares');

        // حذف ستون‌های اضافه شده از insurance_import_logs
        if (Schema::hasTable('insurance_import_logs')) {
            Schema::table('insurance_import_logs', function (Blueprint $table) {
                $table->dropColumn([
                    'user_id', 'total_rows', 'created_count', 'updated_count', 
                    'skipped_count', 'error_count', 'total_insurance_amount',
                    'family_codes', 'updated_family_codes', 'created_family_codes',
                    'family_id', 'row_data', 'status', 'message'
                ]);
            });
        }

        // حذف ستون head_id از families
        if (Schema::hasColumn('families', 'head_id')) {
            Schema::table('families', function (Blueprint $table) {
                $table->dropForeign(['head_id']);
                $table->dropColumn('head_id');
            });
        }

        // حذف ستون‌های اضافه شده از members
        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropColumn([
                    'father_name', 'marital_status', 'education', 
                    'is_employed', 'phone', 'special_conditions', 'problem_type'
                ]);
            });
        }
    }
}; 