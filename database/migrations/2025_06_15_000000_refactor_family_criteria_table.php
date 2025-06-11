<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RefactorFamilyCriteriaTable extends Migration
{
    public function up()
    {
        // 1. انتقال داده‌های قدیمی به سیستم جدید
        $this->migrateOldData();
        
        // 2. حذف ستون‌های قدیمی
        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn(['acceptance_criteria', 'rank_criteria']);
        });

        // 3. حذف ستون غیرضروری
        if (Schema::hasColumn('family_criteria', 'has_criteria')) {
            Schema::table('family_criteria', function (Blueprint $table) {
                $table->dropColumn('has_criteria');
            });
        }
    }

    private function migrateOldData()
    {
        // انتقال از acceptance_criteria
        DB::table('families')
            ->whereNotNull('acceptance_criteria')
            ->chunkById(100, function ($families) {
                foreach ($families as $family) {
                    $criteria = json_decode($family->acceptance_criteria, true);
                    if (is_array($criteria)) {
                        foreach ($criteria as $key => $value) {
                            if ($value) {
                                $rankSetting = DB::table('rank_settings')
                                    ->where('key', $key)
                                    ->first();
                                
                                if ($rankSetting) {
                                    DB::table('family_criteria')->updateOrInsert(
                                        [
                                            'family_id' => $family->id,
                                            'rank_setting_id' => $rankSetting->id
                                        ],
                                        [
                                            'created_at' => now(),
                                            'updated_at' => now()
                                        ]
                                    );
                                }
                            }
                        }
                    }
                }
            });

        // انتقال از rank_criteria
        DB::table('families')
            ->whereNotNull('rank_criteria')
            ->chunkById(100, function ($families) {
                foreach ($families as $family) {
                    $criteriaNames = explode(',', $family->rank_criteria);
                    foreach ($criteriaNames as $name) {
                        $name = trim($name);
                        if (!empty($name)) {
                            $rankSetting = DB::table('rank_settings')
                                ->where('name', $name)
                                ->first();
                            
                            if ($rankSetting) {
                                DB::table('family_criteria')->updateOrInsert(
                                    [
                                        'family_id' => $family->id,
                                        'rank_setting_id' => $rankSetting->id
                                    ],
                                    [
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ]
                                );
                            }
                        }
                    }
                }
            });
    }

    public function down()
    {
        Schema::table('families', function (Blueprint $table) {
            $table->json('acceptance_criteria')->nullable();
            $table->text('rank_criteria')->nullable();
        });
    }
}