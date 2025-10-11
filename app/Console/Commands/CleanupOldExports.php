<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CleanupOldExports extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'exports:cleanup {--days=1 : Number of days to keep exports}';

    /**
     * The description of the console command.
     */
    protected $description = 'Clean up old financial report export files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $threshold = Carbon::now()->subDays($days);
        
        $this->info("🧹 شروع پاک‌سازی فایل‌های export قدیمی‌تر از {$days} روز...");
        
        try {
            // دریافت لیست فایل‌ها از دایرکتوری exports/financial
            $files = Storage::disk('public')->files('exports/financial');
            $deletedCount = 0;
            $totalSize = 0;
            
            if (empty($files)) {
                $this->info('📂 هیچ فایلی برای پاک‌سازی یافت نشد.');
                return 0;
            }
            
            $this->info("🔍 بررسی " . count($files) . " فایل...");
            
            foreach ($files as $file) {
                try {
                    $lastModified = Storage::disk('public')->lastModified($file);
                    $fileDate = Carbon::createFromTimestamp($lastModified);
                    
                    if ($fileDate->isBefore($threshold)) {
                        // محاسبه حجم قبل از حذف
                        $fileSize = Storage::disk('public')->size($file);
                        $totalSize += $fileSize;
                        
                        // حذف فایل
                        Storage::disk('public')->delete($file);
                        $deletedCount++;
                        
                        $this->line("🗑️  حذف شد: {$file} (حجم: " . $this->formatBytes($fileSize) . ")");
                    }
                    
                } catch (\Exception $e) {
                    $this->error("❌ خطا در پردازش فایل {$file}: " . $e->getMessage());
                }
            }
            
            // نمایش خلاصه
            if ($deletedCount > 0) {
                $this->info("✅ پاک‌سازی کامل شد:");
                $this->line("   📄 فایل‌های حذف شده: {$deletedCount}");
                $this->line("   💾 فضای آزاد شده: " . $this->formatBytes($totalSize));
            } else {
                $this->info("✨ همه فایل‌ها جدید هستند - نیازی به پاک‌سازی نیست.");
            }
            
            // ثبت لاگ
            Log::info('پاک‌سازی فایل‌های export انجام شد', [
                'deleted_files' => $deletedCount,
                'freed_space_bytes' => $totalSize,
                'threshold_days' => $days,
                'command' => 'exports:cleanup'
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ خطا در پاک‌سازی: " . $e->getMessage());
            
            Log::error('خطا در پاک‌سازی فایل‌های export', [
                'error' => $e->getMessage(),
                'days' => $days,
                'command' => 'exports:cleanup'
            ]);
            
            return 1;
        }
    }
    
    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
}