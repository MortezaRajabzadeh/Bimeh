<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalyzeSlowQueries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analyze:slow-queries 
                            {--duration=1000 : Minimum query duration in milliseconds}
                            {--limit=50 : Number of queries to show}
                            {--export : Export results to log file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze slow database queries and suggest indexes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 شروع تحلیل کوئری‌های کند...');
        $this->newLine();

        $minDuration = (int) $this->option('duration');
        $limit = (int) $this->option('limit');

        // فعال کردن query log
        DB::enableQueryLog();

        // اجرای کوئری‌های نمونه از گزارش مالی
        $this->info('📊 اجرای کوئری‌های نمونه...');
        $this->runSampleQueries();

        // دریافت و تحلیل queries
        $queries = DB::getQueryLog();
        $this->info(sprintf('✅ تعداد کل کوئری‌ها: %d', count($queries)));
        $this->newLine();

        // فیلتر کوئری‌های کند
        $slowQueries = $this->filterSlowQueries($queries, $minDuration);
        
        if (empty($slowQueries)) {
            $this->info(sprintf('✅ هیچ کوئری کندتر از %d میلی‌ثانیه یافت نشد!', $minDuration));
            return Command::SUCCESS;
        }

        $this->warn(sprintf('⚠️  تعداد کوئری‌های کند: %d', count($slowQueries)));
        $this->newLine();

        // نمایش جدول کوئری‌های کند
        $this->displaySlowQueries($slowQueries, $limit);
        $this->newLine();

        // تحلیل با EXPLAIN
        $this->info('🔬 تحلیل کوئری‌ها با EXPLAIN...');
        $explainResults = $this->analyzeWithExplain($slowQueries);
        $this->newLine();

        // تولید پیشنهادات index
        $this->info('💡 پیشنهادات Index:');
        $suggestions = $this->generateIndexSuggestions($explainResults);
        $this->displayIndexSuggestions($suggestions);
        $this->newLine();

        // نمایش آمار
        $this->displayStatistics($queries, $slowQueries);
        $this->newLine();

        // Export به فایل
        if ($this->option('export')) {
            $this->exportResults($slowQueries, $suggestions);
        }

        return Command::SUCCESS;
    }

    /**
     * اجرای کوئری‌های نمونه
     */
    protected function runSampleQueries(): void
    {
        try {
            // کوئری‌های funding_transactions
            DB::table('funding_transactions')
                ->where('created_at', '>=', now()->subMonths(3))
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            DB::table('funding_transactions')
                ->where('allocated', false)
                ->limit(100)
                ->get();

            // کوئری‌های insurance_shares
            DB::table('insurance_shares')
                ->whereNull('import_log_id')
                ->where('amount', '>', 0)
                ->limit(100)
                ->get();

            // کوئری‌های share_allocation_logs
            DB::table('share_allocation_logs')
                ->where('status', 'completed')
                ->where('total_amount', '>', 0)
                ->orderBy('updated_at', 'desc')
                ->limit(50)
                ->get();

            // کوئری‌های family_funding_allocations
            DB::table('family_funding_allocations')
                ->where('status', '!=', 'pending')
                ->whereNull('transaction_id')
                ->limit(100)
                ->get();

            $this->info('  ✓ کوئری‌های نمونه اجرا شدند');
        } catch (\Exception $e) {
            $this->warn('  ! برخی کوئری‌ها با خطا مواجه شدند: ' . $e->getMessage());
        }
    }

    /**
     * فیلتر کوئری‌های کند
     */
    protected function filterSlowQueries(array $queries, int $minDuration): array
    {
        return array_filter($queries, function ($query) use ($minDuration) {
            return $query['time'] >= $minDuration;
        });
    }

    /**
     * نمایش جدول کوئری‌های کند
     */
    protected function displaySlowQueries(array $slowQueries, int $limit): void
    {
        $rows = [];
        $count = 0;

        foreach ($slowQueries as $query) {
            if ($count >= $limit) {
                break;
            }

            $duration = $query['time'];
            $color = $duration > 2000 ? 'red' : ($duration > 1000 ? 'yellow' : 'white');
            
            $rows[] = [
                $this->formatDuration($duration, $color),
                $this->extractTableName($query['query']),
                Str::limit($query['query'], 80),
            ];

            $count++;
        }

        $this->table(
            ['Duration', 'Table', 'Query'],
            $rows
        );
    }

    /**
     * تحلیل با EXPLAIN
     */
    protected function analyzeWithExplain(array $slowQueries): array
    {
        $results = [];

        foreach ($slowQueries as $query) {
            try {
                $explainQuery = 'EXPLAIN ' . $query['query'];
                $explain = DB::select($explainQuery, $query['bindings'] ?? []);

                if (!empty($explain)) {
                    $explain = (array) $explain[0];
                    $results[] = [
                        'query' => $query['query'],
                        'duration' => $query['time'],
                        'table' => $explain['table'] ?? 'unknown',
                        'type' => $explain['type'] ?? 'unknown',
                        'key' => $explain['key'] ?? null,
                        'rows' => $explain['rows'] ?? 0,
                        'extra' => $explain['Extra'] ?? '',
                    ];
                }
            } catch (\Exception $e) {
                // کوئری قابل تحلیل نیست (مثلاً INSERT/UPDATE)
                continue;
            }
        }

        return $results;
    }

    /**
     * تولید پیشنهادات index
     */
    protected function generateIndexSuggestions(array $explainResults): array
    {
        $suggestions = [];

        foreach ($explainResults as $result) {
            // اگر full table scan است
            if ($result['type'] === 'ALL' || $result['key'] === null) {
                $columns = $this->extractWhereColumns($result['query']);
                
                if (!empty($columns)) {
                    $table = $result['table'];
                    $key = $table . '_' . implode('_', $columns);
                    
                    if (!isset($suggestions[$key])) {
                        $suggestions[$key] = [
                            'table' => $table,
                            'columns' => $columns,
                            'priority' => $this->calculatePriority($result),
                            'reason' => $this->generateReason($result),
                            'occurrences' => 1,
                            'avg_duration' => $result['duration'],
                            'avg_rows' => $result['rows'],
                        ];
                    } else {
                        $suggestions[$key]['occurrences']++;
                        $suggestions[$key]['avg_duration'] = 
                            ($suggestions[$key]['avg_duration'] + $result['duration']) / 2;
                    }
                }
            }
        }

        // مرتب‌سازی بر اساس اولویت
        uasort($suggestions, function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });

        return $suggestions;
    }

    /**
     * نمایش پیشنهادات index
     */
    protected function displayIndexSuggestions(array $suggestions): void
    {
        if (empty($suggestions)) {
            $this->info('  ✅ همه کوئری‌ها از index استفاده می‌کنند!');
            return;
        }

        foreach ($suggestions as $suggestion) {
            $priority = $suggestion['priority'];
            $color = $priority >= 80 ? 'red' : ($priority >= 50 ? 'yellow' : 'white');
            
            $this->line(sprintf(
                '  [<%s>%s</%s>] %s.(%s)',
                $color,
                $this->getPriorityLabel($priority),
                $color,
                $suggestion['table'],
                implode(', ', $suggestion['columns'])
            ));
            
            $this->line(sprintf(
                '     دلیل: %s',
                $suggestion['reason']
            ));
            
            $this->line(sprintf(
                '     تعداد تکرار: %d | میانگین duration: %s | میانگین rows: %d',
                $suggestion['occurrences'],
                $this->formatDuration($suggestion['avg_duration']),
                $suggestion['avg_rows']
            ));
            
            $this->line(sprintf(
                '     <comment>$table->index(%s, \'idx_%s_%s\');</comment>',
                count($suggestion['columns']) > 1 
                    ? sprintf('[\'%s\']', implode("', '", $suggestion['columns']))
                    : "'" . $suggestion['columns'][0] . "'",
                $suggestion['table'],
                implode('_', $suggestion['columns'])
            ));
            
            $this->newLine();
        }
    }

    /**
     * نمایش آمار
     */
    protected function displayStatistics(array $allQueries, array $slowQueries): void
    {
        $totalDuration = array_sum(array_column($allQueries, 'time'));
        $slowDuration = array_sum(array_column($slowQueries, 'time'));
        $avgDuration = count($allQueries) > 0 ? $totalDuration / count($allQueries) : 0;

        $this->info('📈 آمار کلی:');
        $this->line(sprintf('  • تعداد کل کوئری‌ها: %d', count($allQueries)));
        $this->line(sprintf('  • تعداد کوئری‌های کند: %d (%.1f%%)', 
            count($slowQueries), 
            count($allQueries) > 0 ? (count($slowQueries) / count($allQueries) * 100) : 0
        ));
        $this->line(sprintf('  • مجموع زمان کل: %s', $this->formatDuration($totalDuration)));
        $this->line(sprintf('  • مجموع زمان slow queries: %s (%.1f%%)', 
            $this->formatDuration($slowDuration),
            $totalDuration > 0 ? ($slowDuration / $totalDuration * 100) : 0
        ));
        $this->line(sprintf('  • میانگین duration: %s', $this->formatDuration($avgDuration)));
    }

    /**
     * Export نتایج به فایل
     */
    protected function exportResults(array $slowQueries, array $suggestions): void
    {
        $filename = 'slow-queries-' . now()->format('Y-m-d-His') . '.log';
        $path = storage_path('logs/' . $filename);

        $content = [
            'timestamp' => now()->toIso8601String(),
            'total_slow_queries' => count($slowQueries),
            'slow_queries' => $slowQueries,
            'index_suggestions' => array_values($suggestions),
        ];

        file_put_contents($path, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->info(sprintf('📄 نتایج در فایل ذخیره شد: %s', $path));
    }

    /**
     * Helper methods
     */
    protected function formatDuration(float $duration, ?string $color = null): string
    {
        $formatted = sprintf('%.2f ms', $duration);
        return $color ? sprintf('<%s>%s</%s>', $color, $formatted, $color) : $formatted;
    }

    protected function extractTableName(string $query): string
    {
        if (preg_match('/from\s+`?(\w+)`?/i', $query, $matches)) {
            return $matches[1];
        }
        return 'unknown';
    }

    protected function extractWhereColumns(string $query): array
    {
        $columns = [];
        
        // WHERE column = / > / < / !=
        if (preg_match_all('/where\s+`?(\w+)`?\s*[=<>!]/i', $query, $matches)) {
            $columns = array_merge($columns, $matches[1]);
        }
        
        // AND column = / > / < / !=
        if (preg_match_all('/and\s+`?(\w+)`?\s*[=<>!]/i', $query, $matches)) {
            $columns = array_merge($columns, $matches[1]);
        }
        
        // ORDER BY column
        if (preg_match_all('/order\s+by\s+`?(\w+)`?/i', $query, $matches)) {
            $columns = array_merge($columns, $matches[1]);
        }

        return array_unique($columns);
    }

    protected function calculatePriority(array $result): int
    {
        $priority = 0;
        
        // Type: ALL = +40
        if ($result['type'] === 'ALL') {
            $priority += 40;
        }
        
        // Rows: >10000 = +30, >1000 = +20, >100 = +10
        if ($result['rows'] > 10000) {
            $priority += 30;
        } elseif ($result['rows'] > 1000) {
            $priority += 20;
        } elseif ($result['rows'] > 100) {
            $priority += 10;
        }
        
        // Duration: >2000ms = +30, >1000ms = +20
        if ($result['duration'] > 2000) {
            $priority += 30;
        } elseif ($result['duration'] > 1000) {
            $priority += 20;
        }
        
        return min($priority, 100);
    }

    protected function generateReason(array $result): string
    {
        $reasons = [];
        
        if ($result['type'] === 'ALL') {
            $reasons[] = 'Full table scan';
        }
        if ($result['key'] === null) {
            $reasons[] = 'No index used';
        }
        if ($result['rows'] > 1000) {
            $reasons[] = sprintf('%d rows scanned', $result['rows']);
        }
        if ($result['duration'] > 1000) {
            $reasons[] = 'Slow query';
        }
        
        return implode(', ', $reasons) ?: 'Performance optimization';
    }

    protected function getPriorityLabel(int $priority): string
    {
        if ($priority >= 80) {
            return 'CRITICAL';
        } elseif ($priority >= 50) {
            return 'HIGH';
        } elseif ($priority >= 30) {
            return 'MEDIUM';
        } else {
            return 'LOW';
        }
    }
}
