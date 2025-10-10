<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RankSetting;

class RankSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rankSettings = [
            [
                'name' => 'زن سرپرست خانواده',
                'key' => 'female_head',
                'description' => 'خانواده با سرپرست زن (تک‌سرپرست)',
                'weight' => 30,
                'category' => 'social',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'اعتیاد',
                'key' => 'addiction',
                'description' => 'وجود فرد معتاد در خانواده',
                'weight' => 20,
                'category' => 'addiction',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'ازکارافتادگی',
                'key' => 'disability',
                'description' => 'وجود فرد دارای ازکارافتادگی در خانواده',
                'weight' => 25,
                'category' => 'disability',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'بیماری خاص',
                'key' => 'chronic_disease',
                'description' => 'وجود فرد دارای بیماری خاص در خانواده',
                'weight' => 15,
                'category' => 'disease',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => 'بیکاری سرپرست',
                'key' => 'head_unemployment',
                'description' => 'بیکاری سرپرست خانوار',
                'weight' => 18,
                'category' => 'economic',
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'name' => 'خانواده تک‌سرپرست',
                'key' => 'single_parent',
                'description' => 'خانواده با تک سرپرست (زن یا مرد)',
                'weight' => 12,
                'category' => 'social',
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'name' => 'تعداد فرزندان زیاد',
                'key' => 'many_children',
                'description' => 'خانواده با بیش از 3 فرزند',
                'weight' => 8,
                'category' => 'social',
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'name' => 'مسکن نامناسب',
                'key' => 'poor_housing',
                'description' => 'زندگی در مسکن نامناسب یا اجاره‌ای',
                'weight' => 10,
                'category' => 'economic',
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'name' => 'درآمد پایین',
                'key' => 'low_income',
                'description' => 'درآمد خانوار زیر خط فقر',
                'weight' => 22,
                'category' => 'economic',
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'name' => 'عدم تحصیلات',
                'key' => 'no_education',
                'description' => 'بی‌سوادی سرپرست خانوار',
                'weight' => 5,
                'category' => 'social',
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'سالمند نیازمند مراقبت',
                'key' => 'elderly_care',
                'description' => 'وجود سالمند نیازمند مراقبت در خانواده',
                'weight' => 14,
                'category' => 'social',
                'is_active' => true,
                'sort_order' => 11,
            ],
        ];

        foreach ($rankSettings as $setting) {
            RankSetting::updateOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
