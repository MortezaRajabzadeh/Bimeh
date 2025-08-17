<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // بررسی وجود رکورد بیکاری در جدول
        $exists = DB::table('rank_settings')
            ->where('name', 'بیکاری')
            ->orWhere('key', 'unemployment')
            ->exists();
            
        if (!$exists) {
            // محاسبه بزرگترین sort_order موجود
            $maxSortOrder = DB::table('rank_settings')->max('sort_order') ?? 0;
            
            // اضافه کردن بیکاری به جدول rank_settings
            DB::table('rank_settings')->insert([
                'name' => 'بیکاری',
                'key' => 'unemployment',
                'description' => 'وضعیت بیکاری یا عدم داشتن شغل ثابت در خانواده',
                'weight' => 8, // وزن بالا برای بیکاری
                'category' => 'economic',
                'requires_document' => 1,
                'is_active' => 1,
                'sort_order' => $maxSortOrder + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            echo "✅ معیار 'بیکاری' با موفقیت اضافه شد.\n";
        } else {
            echo "⚠️ معیار 'بیکاری' از قبل موجود است.\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // حذف رکورد بیکاری از جدول
        DB::table('rank_settings')
            ->where('key', 'unemployment')
            ->delete();
        
        echo "✅ معیار 'بیکاری' حذف شد.\n";
    }
};
