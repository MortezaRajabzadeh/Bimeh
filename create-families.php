<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Family;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    // پیدا کردن خیریه
    $charity = User::where('role', 'charity')->first();
    
    if (!$charity) {
        echo "هیچ کاربر خیریه‌ای یافت نشد.\n";
        exit(1);
    }
    
    // ایجاد 5 خانواده با 2 تا 5 عضو
    for ($i = 0; $i < 5; $i++) {
        $family = Family::create([
            'charity_id' => $charity->organization_id,
            'region_id' => rand(1, 5),
            'family_code' => 'F' . rand(1000, 9999),
            'address' => 'آدرس آزمایشی شماره ' . ($i + 1),
            'postal_code' => (string) rand(1000000000, 9999999999),
            'housing_status' => ['rental', 'owned', 'relative'][rand(0, 2)],
            'is_insured' => rand(0, 1),
            'status' => 'approved',
            'poverty_confirmed' => 1
        ]);
        
        $memberCount = rand(2, 5);
        for ($j = 0; $j < $memberCount; $j++) {
            $isHeadOfHousehold = ($j == 0);
            Member::create([
                'family_id' => $family->id,
                'first_name' => 'نام' . ($j + 1),
                'last_name' => 'خانوادگی' . ($i + 1),
                'national_code' => (string) rand(1000000000, 9999999999),
                'birth_date' => now()->subYears(rand(10, 70))->format('Y-m-d'),
                'gender' => rand(0, 1) ? 'male' : 'female',
                'is_head_of_household' => $isHeadOfHousehold,
                'relationship' => $isHeadOfHousehold ? 'self' : ['spouse', 'child', 'parent', 'sibling'][rand(0, 3)]
            ]);
        }
    }
    
    echo "تعداد " . Family::count() . " خانواده و " . Member::count() . " عضو با موفقیت ایجاد شد.\n";
} catch (Exception $e) {
    echo "خطا: " . $e->getMessage() . "\n";
    echo "در فایل: " . $e->getFile() . " خط " . $e->getLine() . "\n";
} 