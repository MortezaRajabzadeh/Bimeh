<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $families = App\Models\Family::withCount('members')->get();
    
    echo "تعداد خانواده‌ها: " . $families->count() . PHP_EOL;
    echo "تعداد اعضا: " . App\Models\Member::count() . PHP_EOL;
    
    // نمایش تعداد خانواده‌های تایید شده/رد شده/در انتظار
    $pendingCount = $families->where('status', 'pending')->count();
    $approvedCount = $families->where('status', 'approved')->count();
    $rejectedCount = $families->where('status', 'rejected')->count();
    
    echo "خانواده‌های در انتظار بررسی: " . $pendingCount . PHP_EOL;
    echo "خانواده‌های تایید شده: " . $approvedCount . PHP_EOL;
    echo "خانواده‌های رد شده: " . $rejectedCount . PHP_EOL;
    
    // نمایش تعداد خانواده‌های بیمه شده
    $insuredCount = $families->where('is_insured', 1)->count();
    $uninsuredCount = $families->where('is_insured', 0)->count();
    
    echo "خانواده‌های بیمه شده: " . $insuredCount . PHP_EOL;
    echo "خانواده‌های بیمه نشده: " . $uninsuredCount . PHP_EOL;
    
    // نمایش خانواده‌ها و تعداد اعضای هر خانواده
    echo PHP_EOL . "لیست خانواده‌ها و تعداد اعضا:" . PHP_EOL . str_repeat('-', 60) . PHP_EOL;
    
    // بررسی اعضای هر خانواده
    echo sprintf("%-12s | %-12s | %-10s | %-5s | %-10s | %-10s", 'کد', 'تعداد اعضا', 'وضعیت', 'بیمه', 'منطقه', 'آدرس') . PHP_EOL;
    echo str_repeat('-', 60) . PHP_EOL;
    
    foreach ($families as $family) {
        $region = App\Models\Region::find($family->region_id);
        $regionName = $region ? $region->name : 'نامشخص';
        
        echo sprintf(
            "%-12s | %-12d | %-10s | %-5s | %-10s | %-20s", 
            $family->family_code, 
            $family->members_count, 
            $family->status, 
            $family->is_insured ? 'دارد' : 'ندارد',
            $regionName,
            mb_substr($family->address, 0, 20)
        ) . PHP_EOL;
    }
    
    // بررسی جداول نیاز به اصلاح
    echo PHP_EOL . "بررسی نیاز به اصلاح جداول:" . PHP_EOL . str_repeat('-', 40) . PHP_EOL;
    $missingHeads = 0;
    
    foreach ($families as $family) {
        $headCount = App\Models\Member::where('family_id', $family->id)->where('is_head', 1)->count();
        if ($headCount != 1) {
            $missingHeads++;
            echo "خانواده {$family->family_code} دارای {$headCount} سرپرست است." . PHP_EOL;
        }
    }
    
    if ($missingHeads == 0) {
        echo "همه خانواده‌ها دارای یک سرپرست هستند." . PHP_EOL;
    } else {
        echo "تعداد {$missingHeads} خانواده نیاز به اصلاح دارند." . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "خطا: " . $e->getMessage() . PHP_EOL;
} 