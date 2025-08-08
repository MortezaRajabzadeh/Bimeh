<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\ClaimsSummary;

class ClaimsCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'claims:cache {action=warm : Action to perform (clear|warm|stats)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage claims cache operations (clear, warm-up, or show statistics)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        switch ($action) {
            case 'clear':
                $this->clearCache();
                break;
            
            case 'warm':
                $this->warmupCache();
                break;
                
            case 'stats':
                $this->showCacheStats();
                break;
                
            default:
                $this->error('Invalid action. Use: clear, warm, or stats');
                return 1;
        }

        return 0;
    }

    /**
     * پاک کردن تمام کش‌های مربوط به خسارات
     */
    protected function clearCache()
    {
        $this->info('Clearing claims cache...');
        
        // پاک کردن کش‌های تگ شده
        Cache::tags(['claims_summary'])->flush();
        
        // پاک کردن کش‌های خاص
        Cache::forget('insurance.families.list');
        Cache::forget('insurance.funding_transactions.list');
        Cache::forget('claims_overall_stats');
        Cache::forget('available_insurance_types');
        
        // پاک کردن کش‌های صفحه‌بندی
        for ($page = 1; $page <= 100; $page++) {
            foreach ([10, 15, 30, 50, 100] as $perPage) {
                Cache::forget("insurance.claims.page.{$page}.perpage.{$perPage}");
            }
        }
        
        $this->info('✅ Claims cache cleared successfully.');
    }

    /**
     * Warm-up کردن کش‌ها با داده‌های پرکاربرد
     */
    protected function warmupCache()
    {
        $this->info('Warming up claims cache...');
        
        $progressBar = $this->output->createProgressBar(6);
        $progressBar->start();

        // 1. کش کردن آمار کلی
        ClaimsSummary::getOverallStats();
        $progressBar->advance();

        // 2. کش کردن خلاصه ماه جاری
        ClaimsSummary::getSummaryByDateAndType(
            date('Y-m-01'),
            date('Y-m-t')
        );
        $progressBar->advance();

        // 3. کش کردن خلاصه ماهانه سال جاری
        ClaimsSummary::getMonthlySummary(date('Y'));
        $progressBar->advance();

        // 4. کش کردن Top خانواده‌ها
        ClaimsSummary::getTopFamiliesByClaims(20);
        $progressBar->advance();

        // 5. کش کردن لیست انواع بیمه
        DB::table('funding_transactions')
            ->select('description')
            ->whereNotNull('description')
            ->where('description', '!=', '')
            ->distinct()
            ->orderBy('description')
            ->pluck('description')
            ->toArray();
        $progressBar->advance();

        // 6. کش کردن صفحه اول خسارات
        DB::table('insurance_allocations')
            ->with(['family', 'transaction'])
            ->latest()
            ->paginate(10);
        $progressBar->advance();

        $progressBar->finish();
        $this->newLine();
        $this->info('✅ Claims cache warmed up successfully.');
    }

    /**
     * نمایش آمار کش
     */
    protected function showCacheStats()
    {
        $this->info('Claims Cache Statistics:');
        $this->newLine();

        // آمار کلی دیتابیس
        $totalClaims = DB::table('insurance_allocations')->count();
        $totalFamilies = DB::table('families')->count();
        $totalAmount = DB::table('insurance_allocations')->sum('amount');

        $this->table([
            'Metric', 'Count'
        ], [
            ['Total Claims', number_format($totalClaims)],
            ['Total Families', number_format($totalFamilies)],
            ['Total Amount', number_format($totalAmount) . ' Toman'],
        ]);

        // بررسی وجود کش‌های کلیدی
        $cacheKeys = [
            'claims_overall_stats' => 'Overall Statistics',
            'available_insurance_types' => 'Insurance Types',
            'insurance.families.list' => 'Families List',
            'insurance.funding_transactions.list' => 'Transactions List',
        ];

        $this->newLine();
        $this->info('Key Cache Status:');
        
        foreach ($cacheKeys as $key => $description) {
            $status = Cache::has($key) ? '✅ Cached' : '❌ Missing';
            $this->line("  {$description}: {$status}");
        }

        // Memory usage (if available)
        if (function_exists('memory_get_usage')) {
            $this->newLine();
            $this->info('Memory Usage: ' . $this->formatBytes(memory_get_usage(true)));
        }
    }

    /**
     * فرمت کردن بایت‌ها به واحد قابل خواندن
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
