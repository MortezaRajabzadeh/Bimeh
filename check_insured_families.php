<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Family;
use App\Models\FamilyInsurance;

echo "=== بررسی خانواده‌های با بیمه فعال ===\n\n";

// 1. همه family_insurances
$allInsurances = FamilyInsurance::with('family')->get();
echo "📊 تعداد کل بیمه‌ها: " . $allInsurances->count() . "\n\n";

foreach ($allInsurances as $insurance) {
    echo "- ID: {$insurance->id}, Family: {$insurance->family_id}, Status: {$insurance->status}, ";
    echo "Family Code: " . ($insurance->family ? $insurance->family->family_code : 'N/A') . "\n";
}

echo "\n";

// 2. بیمه‌های فعال
$activeInsurances = FamilyInsurance::where('status', 'active')->with('family')->get();
echo "✅ بیمه‌های فعال (status=active): " . $activeInsurances->count() . "\n\n";

foreach ($activeInsurances as $insurance) {
    echo "- ID: {$insurance->id}, Family: {$insurance->family_id}, ";
    echo "Family Code: " . ($insurance->family ? $insurance->family->family_code : 'N/A') . "\n";
}

echo "\n";

// 3. خانواده‌های با بیمه فعال (مثل کوئری کنترلر)
$insuredFamilies = Family::whereHas('insurances', function($query) {
    $query->where('status', 'active');
})->with(['insurances' => function($query) {
    $query->where('status', 'active');
}])->get();

echo "👥 خانواده‌های با بیمه فعال (از کوئری کنترلر): " . $insuredFamilies->count() . "\n\n";

foreach ($insuredFamilies as $family) {
    echo "- Family ID: {$family->id}, Code: {$family->family_code}\n";
    foreach ($family->insurances as $ins) {
        echo "  └─ Insurance ID: {$ins->id}, Status: {$ins->status}, Amount: {$ins->premium_amount}\n";
    }
}

echo "\n=== پایان ===\n";
