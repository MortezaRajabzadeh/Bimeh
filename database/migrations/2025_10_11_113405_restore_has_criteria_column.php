<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * این migration ستون has_criteria را که در migration قبلی حذف شده بود،
     * دوباره اضافه می‌کند چون کد هنوز به آن وابسته است.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('family_criteria', 'has_criteria')) {
            Schema::table('family_criteria', function (Blueprint $table) {
                $table->boolean('has_criteria')
                    ->default(true)
                    ->after('rank_setting_id')
                    ->comment('آیا این معیار در خانواده وجود دارد');
            });

            // تنظیم مقدار پیش‌فرض برای رکوردهای موجود
            // همه رکوردهای موجود را به true تنظیم می‌کنیم
            // چون اگر رکورد وجود دارد یعنی این خانواده آن معیار را دارد
            DB::table('family_criteria')->update(['has_criteria' => true]);

            // اضافه کردن index برای بهبود عملکرد
            Schema::table('family_criteria', function (Blueprint $table) {
                if (!Schema::hasIndex('family_criteria', 'family_criteria_family_id_has_criteria_index')) {
                    $table->index(['family_id', 'has_criteria'], 'family_criteria_family_id_has_criteria_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('family_criteria', 'has_criteria')) {
            // حذف index اگر وجود دارد
            Schema::table('family_criteria', function (Blueprint $table) {
                $table->dropIndex('family_criteria_family_id_has_criteria_index');
            });

            // حذف ستون
            Schema::table('family_criteria', function (Blueprint $table) {
                $table->dropColumn('has_criteria');
            });
        }
    }
};
