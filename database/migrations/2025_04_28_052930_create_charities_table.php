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
        Schema::create('charities', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // نام خیریه
            $table->string('slug')->unique()->nullable(); // اسلاگ برای url
            $table->text('description')->nullable(); // توضیحات خیریه
            $table->string('logo')->nullable(); // آدرس لوگوی خیریه
            $table->string('phone')->nullable(); // شماره تماس
            $table->string('email')->nullable(); // ایمیل
            $table->string('website')->nullable(); // وب‌سایت
            $table->text('address')->nullable(); // آدرس
            $table->boolean('is_active')->default(true); // وضعیت فعال/غیرفعال
            $table->timestamps();
            $table->softDeletes(); // حذف نرم - برای حفظ سوابق
        });
        
        // ایجاد جدول رابطه بین خانواده و خیریه (در صورت نیاز)
        Schema::table('families', function (Blueprint $table) {
            if (!Schema::hasColumn('families', 'charity_id')) {
                $table->foreignId('charity_id')->nullable()->after('region_id')
                      ->constrained('charities')->nullOnDelete();
            }
        });
        
        // ایجاد جدول رابطه بین اعضای خانواده و خیریه (در صورت نیاز)
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasColumn('members', 'charity_id')) {
                $table->foreignId('charity_id')->nullable()->after('family_id')
                      ->constrained('charities')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // برای جلوگیری از خطا، ابتدا بررسی می‌کنیم آیا ستون وجود دارد
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'charity_id')) {
                $table->dropConstrainedForeignId('charity_id');
            }
        });
        
        Schema::table('families', function (Blueprint $table) {
            if (Schema::hasColumn('families', 'charity_id')) {
                $table->dropConstrainedForeignId('charity_id');
            }
        });
        
        Schema::dropIfExists('charities');
    }
};
