<?php

namespace Database\Seeders;

use App\Models\Family;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AcceptanceCriteriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // لیست معیارهای پذیرش ممکن
        $allCriteria = [
            'از کار افتادگی',
            'سرپرست خانوار زن',
            'بیماری خاص',
            'یتیم',
            'بی سرپرست',
            'معلولیت',
            'بیکاری',
            'سالمند',
            'کودکان کار',
            'ساکن مناطق محروم'
        ];
        
        // دریافت همه خانواده‌ها
        $families = Family::all();
        
        foreach ($families as $family) {
            // برای هر خانواده 1 تا 3 معیار پذیرش انتخاب می‌کنیم
            $numCriteria = rand(1, 3);
            
            // انتخاب تصادفی معیارها از لیست
            $criteriaKeys = array_rand($allCriteria, $numCriteria);
            
            // تبدیل به آرایه در صورتی که فقط یک معیار انتخاب شده باشد
            if (!is_array($criteriaKeys)) {
                $criteriaKeys = [$criteriaKeys];
            }
            
            // استخراج معیارهای انتخابی
            $selectedCriteria = [];
            foreach ($criteriaKeys as $key) {
                $selectedCriteria[] = $allCriteria[$key];
            }
            
            // ذخیره معیارها در فیلد acceptance_criteria
            $family->acceptance_criteria = $selectedCriteria;
            $family->save();
            
            $this->command->info("معیارهای پذیرش برای خانواده شماره {$family->id} به‌روز شد.");
        }
    }
}
