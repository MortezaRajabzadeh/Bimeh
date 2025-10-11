<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
    
    $deleted1 = DB::table('insurance_import_logs')->delete();
    echo "✅ پاک شد: insurance_import_logs ({$deleted1} رکورد)\n";
    
    $deleted2 = DB::table('share_allocation_logs')->delete();
    echo "✅ پاک شد: share_allocation_logs ({$deleted2} رکورد)\n";
    
    $deleted3 = DB::table('funding_transactions')->delete();
    echo "✅ پاک شد: funding_transactions ({$deleted3} رکورد)\n";
    
    $deleted4 = DB::table('insurance_shares')->delete();
    echo "✅ پاک شد: insurance_shares (سهم‌های بیمه) ({$deleted4} رکورد)\n";
    
    $deleted5 = DB::table('family_insurances')->delete();
    echo "✅ پاک شد: family_insurances (بیمه خانوارها) ({$deleted5} رکورد)\n";
    
    $deleted6 = DB::table('insurance_allocations')->delete();
    echo "✅ پاک شد: insurance_allocations (تخصیص بیمه) ({$deleted6} رکورد)\n";
    
    DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    
    echo "\n🎉 همه دیتاها با موفقیت پاک شدند!\n";
    
} catch (\Exception $e) {
    echo "❌ خطا: " . $e->getMessage() . "\n";
}
