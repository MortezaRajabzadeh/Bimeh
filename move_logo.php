<?php
/**
 * انتقال لوگوی موجود به مسیر جدید
 */

require_once 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$app->boot();

$oldPath = 'C:\laragon\www\laravel-super-starter\storage\app\public\organizations\logos\688de491e14a3.webp';
$newDir = 'C:\laragon\www\laravel-super-starter\public\images\organizations\logos';
$newPath = $newDir . '\688de491e14a3.webp';
$relativePath = 'images/organizations/logos/688de491e14a3.webp';

echo "انتقال فایل لوگو...\n";

// ایجاد دایرکتوری جدید
if (!is_dir($newDir)) {
    mkdir($newDir, 0755, true);
    echo "✅ دایرکتوری جدید ایجاد شد: $newDir\n";
}

// کپی فایل
if (file_exists($oldPath)) {
    if (copy($oldPath, $newPath)) {
        echo "✅ فایل با موفقیت کپی شد: $newPath\n";
        
        // بروزرسانی دیتابیس
        try {
            DB::table('organizations')
                ->where('logo_path', 'organizations/logos/688de491e14a3.webp')
                ->update(['logo_path' => $relativePath]);
            
            echo "✅ دیتابیس بروزرسانی شد\n";
            
            // بررسی نهایی
            $org = DB::table('organizations')->where('logo_path', $relativePath)->first();
            if ($org) {
                echo "✅ رکورد پیدا شد - ID: {$org->id}\n";
                echo "📍 مسیر جدید: {$org->logo_path}\n";
                
                // تست URL
                $url = asset($relativePath);
                echo "🌐 URL جدید: $url\n";
            }
            
        } catch (Exception $e) {
            echo "❌ خطا در بروزرسانی دیتابیس: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "❌ خطا در کپی فایل\n";
    }
} else {
    echo "❌ فایل اصلی پیدا نشد: $oldPath\n";
}
