<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\FundingTransaction;
use App\Models\FamilyFundingAllocation;
use App\Models\InsuranceAllocation;
use App\Models\InsuranceImportLog;
use App\Models\InsurancePayment;
use App\Models\InsuranceShare;
use App\Models\ShareAllocationLog;

echo "=== بررسی تراکنش‌ها ===\n\n";

// 1. FundingTransaction
$count1 = FundingTransaction::count();
echo "1. FundingTransaction: {$count1}\n";
if ($count1 > 0) {
    $items = FundingTransaction::with('source')->get();
    foreach ($items as $item) {
        echo "   - ID: {$item->id}, Amount: {$item->amount}, Source: " . ($item->source ? $item->source->name : 'N/A') . "\n";
    }
}

// 2. FamilyFundingAllocation
$count2 = FamilyFundingAllocation::count();
echo "\n2. FamilyFundingAllocation: {$count2}\n";
if ($count2 > 0) {
    $items = FamilyFundingAllocation::with('family')->get();
    foreach ($items as $item) {
        echo "   - ID: {$item->id}, Family: {$item->family_id}, Amount: {$item->amount}, ";
        echo "Family Code: " . ($item->family ? $item->family->family_code : 'N/A') . "\n";
    }
}

// 3. InsuranceAllocation
$count3 = InsuranceAllocation::count();
echo "\n3. InsuranceAllocation: {$count3}\n";
if ($count3 > 0) {
    $items = InsuranceAllocation::with('family')->get();
    foreach ($items as $item) {
        echo "   - ID: {$item->id}, Family: {$item->family_id}, Amount: {$item->amount}\n";
    }
}

// 4. InsuranceImportLog
$count4 = InsuranceImportLog::count();
echo "\n4. InsuranceImportLog: {$count4}\n";
if ($count4 > 0) {
    $items = InsuranceImportLog::all();
    foreach ($items as $item) {
        echo "   - ID: {$item->id}, Amount: {$item->total_insurance_amount}\n";
    }
}

// 5. InsurancePayment
$count5 = InsurancePayment::count();
echo "\n5. InsurancePayment: {$count5}\n";
if ($count5 > 0) {
    $items = InsurancePayment::all();
    foreach ($items as $item) {
        echo "   - ID: {$item->id}, Amount: {$item->total_amount}\n";
    }
}

// 6. InsuranceShare
$count6 = InsuranceShare::count();
echo "\n6. InsuranceShare: {$count6}\n";
if ($count6 > 0) {
    $items = InsuranceShare::with('familyInsurance.family')->get();
    foreach ($items as $item) {
        $familyCode = 'N/A';
        if ($item->familyInsurance && $item->familyInsurance->family) {
            $familyCode = $item->familyInsurance->family->family_code;
        }
        echo "   - ID: {$item->id}, Amount: {$item->amount}, Family Code: {$familyCode}\n";
    }
}

// 7. ShareAllocationLog
$count7 = ShareAllocationLog::count();
echo "\n7. ShareAllocationLog: {$count7}\n";
if ($count7 > 0) {
    $items = ShareAllocationLog::all();
    foreach ($items as $item) {
        echo "   - ID: {$item->id}, Families Count: {$item->families_count}, Amount: {$item->total_amount}\n";
    }
}

$total = $count1 + $count2 + $count3 + $count4 + $count5 + $count6 + $count7;
echo "\n📊 جمع کل: {$total} تراکنش\n";

echo "\n=== پایان ===\n";
