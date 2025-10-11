<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckDatabaseIndexes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:check-indexes 
                            {--table= : Specific table to check}
                            {--missing : Show only missing recommended indexes}
                            {--generate-migration : Generate migration for missing indexes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check existing database indexes and compare with recommendations';

    /**
     * جداول مالی برای بررسی
     */
    protected array $financialTables = [
        'funding_transactions',
        'insurance_shares',
        'share_allocation_logs',
        'family_funding_allocations',
        'family_insurances',
        'insurance_allocations',
    ];

    /**
     * Index‌های توصیه شده برای هر جدول
     */
    protected array $recommendedIndexes = [
        'funding_transactions' => [
            ['columns' => ['created_at'], 'reason' => 'Date range queries in FinancialReportController'],
            ['columns' => ['allocated'], 'reason' => 'Allocated/non-allocated filter in Repository'],
            ['columns' => ['status', 'created_at'], 'reason' => 'Status filter with ordering'],
        ],
        'insurance_shares' => [
            ['columns' => ['family_insurance_id', 'payer_type'], 'reason' => 'Existing index - should be present'],
            ['columns' => ['is_paid', 'payment_date'], 'reason' => 'Existing index - should be present'],
            ['columns' => ['family_insurance_id', 'amount'], 'reason' => 'Filter by family and amount > 0'],
            ['columns' => ['import_log_id'], 'reason' => 'whereNull filter for manual shares'],
        ],
        'share_allocation_logs' => [
            ['columns' => ['status', 'total_amount'], 'reason' => 'Status and amount filter in Repository'],
            ['columns' => ['updated_at'], 'reason' => 'Ordering by updated_at'],
            ['columns' => ['batch_id'], 'reason' => 'Batch lookup'],
            ['columns' => ['file_hash'], 'reason' => 'Duplicate validation'],
        ],
        'family_funding_allocations' => [
            ['columns' => ['status', 'transaction_id'], 'reason' => 'Status filter with null transaction'],
            ['columns' => ['approved_at'], 'reason' => 'Ordering by approval date'],
            ['columns' => ['funding_source_id', 'status'], 'reason' => 'Sum calculations by funding source'],
        ],
        'family_insurances' => [
            ['columns' => ['family_id', 'insurance_type'], 'reason' => 'Existing index - should be present'],
            ['columns' => ['status'], 'reason' => 'Status filter in whereHas queries'],
            ['columns' => ['family_id', 'status'], 'reason' => 'whereHas with status filter'],
            ['columns' => ['created_at'], 'reason' => 'Ordering and date filters'],
            ['columns' => ['status', 'premium_amount'], 'reason' => 'Status with premium amount filter'],
        ],
        'insurance_allocations' => [
            ['columns' => ['created_at'], 'reason' => 'Date range queries in DashboardStats'],
            ['columns' => ['family_id', 'amount', 'issue_date'], 'reason' => 'Duplicate check in ClaimsImportService'],
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 بررسی Index‌های پایگاه داده...');
        $this->newLine();

        $tablesToCheck = $this->option('table') 
            ? [$this->option('table')] 
            : $this->financialTables;

        $allResults = [];

        foreach ($tablesToCheck as $table) {
            if (!Schema::hasTable($table)) {
                $this->warn(sprintf('⚠️  جدول %s وجود ندارد', $table));
                continue;
            }

            $result = $this->checkTable($table);
            $allResults[$table] = $result;

            if (!$this->option('missing') || !empty($result['missing'])) {
                $this->displayTableResults($table, $result);
            }
        }

        $this->newLine();
        $this->displaySummary($allResults);

        // تولید migration اگر درخواست شده باشد
        if ($this->option('generate-migration')) {
            $this->generateMigration($allResults);
        }

        return Command::SUCCESS;
    }

    /**
     * بررسی یک جدول
     */
    protected function checkTable(string $table): array
    {
        $existingIndexes = $this->getTableIndexes($table);
        $recommended = $this->recommendedIndexes[$table] ?? [];

        $existing = [];
        $missing = [];
        $extra = [];

        // بررسی index‌های توصیه شده
        foreach ($recommended as $recommendedIndex) {
            $columns = $recommendedIndex['columns'];
            $found = $this->hasIndex($existingIndexes, $columns);

            if ($found) {
                $existing[] = [
                    'columns' => $columns,
                    'reason' => $recommendedIndex['reason'],
                    'index_name' => $found,
                ];
            } else {
                $missing[] = [
                    'columns' => $columns,
                    'reason' => $recommendedIndex['reason'],
                ];
            }
        }

        // شناسایی index‌های اضافی (غیرضروری یا تکراری)
        foreach ($existingIndexes as $indexName => $indexColumns) {
            // نادیده گرفتن PRIMARY و UNIQUE constraints
            if ($indexName === 'PRIMARY' || str_contains($indexName, 'unique')) {
                continue;
            }

            $isRecommended = false;
            foreach ($recommended as $rec) {
                if ($this->columnsMatch($rec['columns'], $indexColumns)) {
                    $isRecommended = true;
                    break;
                }
            }

            if (!$isRecommended && !$this->isForeignKeyIndex($table, $indexName)) {
                $extra[] = [
                    'name' => $indexName,
                    'columns' => $indexColumns,
                ];
            }
        }

        return [
            'existing' => $existing,
            'missing' => $missing,
            'extra' => $extra,
        ];
    }

    /**
     * دریافت index‌های موجود جدول
     */
    protected function getTableIndexes(string $table): array
    {
        $indexes = [];
        
        try {
            $results = DB::select("SHOW INDEXES FROM `{$table}`");
            
            foreach ($results as $row) {
                $indexName = $row->Key_name;
                $columnName = $row->Column_name;
                $seqInIndex = $row->Seq_in_index;
                
                if (!isset($indexes[$indexName])) {
                    $indexes[$indexName] = [];
                }
                
                $indexes[$indexName][$seqInIndex - 1] = $columnName;
            }
            
            // مرتب‌سازی ستون‌ها بر اساس sequence
            foreach ($indexes as $indexName => $columns) {
                ksort($indexes[$indexName]);
                $indexes[$indexName] = array_values($indexes[$indexName]);
            }
        } catch (\Exception $e) {
            $this->error(sprintf('خطا در دریافت indexes جدول %s: %s', $table, $e->getMessage()));
        }

        return $indexes;
    }

    /**
     * بررسی وجود index با ستون‌های مشخص
     */
    protected function hasIndex(array $existingIndexes, array $columns): ?string
    {
        foreach ($existingIndexes as $indexName => $indexColumns) {
            if ($this->columnsMatch($columns, $indexColumns)) {
                return $indexName;
            }
        }
        return null;
    }

    /**
     * بررسی تطابق ستون‌ها
     */
    protected function columnsMatch(array $cols1, array $cols2): bool
    {
        return count($cols1) === count($cols2) && 
               array_diff($cols1, $cols2) === array_diff($cols2, $cols1);
    }

    /**
     * بررسی اینکه آیا index یک FK است
     */
    protected function isForeignKeyIndex(string $table, string $indexName): bool
    {
        try {
            $fks = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$table, $indexName]);
            
            return !empty($fks);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * نمایش نتایج یک جدول
     */
    protected function displayTableResults(string $table, array $result): void
    {
        $this->line(sprintf('📊 <info>%s</info>', $table));
        $this->line(str_repeat('─', 80));

        // Index‌های موجود
        if (!empty($result['existing'])) {
            $this->line('  <fg=green>✅ Index‌های موجود:</>');
            foreach ($result['existing'] as $index) {
                $this->line(sprintf(
                    '     • (%s) - %s',
                    implode(', ', $index['columns']),
                    $index['reason']
                ));
                $this->line(sprintf('       <comment>Index: %s</comment>', $index['index_name']));
            }
            $this->newLine();
        }

        // Index‌های missing
        if (!empty($result['missing'])) {
            $this->line('  <fg=red>❌ Index‌های مفقود:</>');
            foreach ($result['missing'] as $index) {
                $this->line(sprintf(
                    '     • (%s) - %s',
                    implode(', ', $index['columns']),
                    $index['reason']
                ));
                
                // پیشنهاد migration code
                $indexCode = $this->generateIndexCode($table, $index['columns']);
                $this->line(sprintf('       <comment>%s</comment>', $indexCode));
            }
            $this->newLine();
        }

        // Index‌های اضافی
        if (!empty($result['extra'])) {
            $this->line('  <fg=yellow>⚠️  Index‌های احتمالاً غیرضروری:</>');
            foreach ($result['extra'] as $index) {
                $this->line(sprintf(
                    '     • %s (%s)',
                    $index['name'],
                    implode(', ', $index['columns'])
                ));
            }
            $this->newLine();
        }

        $this->newLine();
    }

    /**
     * نمایش خلاصه کلی
     */
    protected function displaySummary(array $allResults): void
    {
        $totalExisting = 0;
        $totalMissing = 0;
        $totalExtra = 0;

        foreach ($allResults as $result) {
            $totalExisting += count($result['existing']);
            $totalMissing += count($result['missing']);
            $totalExtra += count($result['extra']);
        }

        $this->info('📈 خلاصه کلی:');
        $this->line(sprintf('  • جداول بررسی شده: %d', count($allResults)));
        $this->line(sprintf('  • Index‌های موجود: <fg=green>%d</>', $totalExisting));
        $this->line(sprintf('  • Index‌های مفقود: <fg=red>%d</>', $totalMissing));
        $this->line(sprintf('  • Index‌های اضافی: <fg=yellow>%d</>', $totalExtra));

        if ($totalMissing === 0) {
            $this->newLine();
            $this->info('🎉 تمام index‌های توصیه شده موجود هستند!');
        } else {
            $this->newLine();
            $this->warn(sprintf('⚠️  %d index مفقود شناسایی شد. برای اضافه کردن از --generate-migration استفاده کنید.', $totalMissing));
        }
    }

    /**
     * تولید کد index
     */
    protected function generateIndexCode(string $table, array $columns): string
    {
        $indexName = 'idx_' . $table . '_' . implode('_', $columns);
        
        if (count($columns) === 1) {
            return sprintf("\$table->index('%s', '%s');", $columns[0], $indexName);
        } else {
            $columnsStr = "['" . implode("', '", $columns) . "']";
            return sprintf("\$table->index(%s, '%s');", $columnsStr, $indexName);
        }
    }

    /**
     * تولید migration برای index‌های missing
     */
    protected function generateMigration(array $allResults): void
    {
        $missingIndexes = [];

        foreach ($allResults as $table => $result) {
            if (!empty($result['missing'])) {
                $missingIndexes[$table] = $result['missing'];
            }
        }

        if (empty($missingIndexes)) {
            $this->info('✅ هیچ index مفقودی برای ایجاد migration وجود ندارد.');
            return;
        }

        $timestamp = now()->format('Y_m_d_His');
        $filename = "database/migrations/{$timestamp}_add_missing_indexes.php";
        
        $content = $this->generateMigrationContent($missingIndexes);
        
        file_put_contents(base_path($filename), $content);
        
        $this->info(sprintf('📄 Migration ایجاد شد: %s', $filename));
        $this->line(sprintf('   اجرا با: <comment>php artisan migrate</comment>'));
    }

    /**
     * تولید محتوای migration
     */
    protected function generateMigrationContent(array $missingIndexes): string
    {
        $upMethods = [];
        $downMethods = [];

        foreach ($missingIndexes as $table => $indexes) {
            $tableUp = "        Schema::table('{$table}', function (Blueprint \$table) {\n";
            $tableDown = "        Schema::table('{$table}', function (Blueprint \$table) {\n";

            foreach ($indexes as $index) {
                $indexName = 'idx_' . $table . '_' . implode('_', $index['columns']);
                $comment = "            // " . $index['reason'] . "\n";
                
                if (count($index['columns']) === 1) {
                    $tableUp .= $comment;
                    $tableUp .= "            \$table->index('{$index['columns'][0]}', '{$indexName}');\n";
                } else {
                    $columnsStr = "['" . implode("', '", $index['columns']) . "']";
                    $tableUp .= $comment;
                    $tableUp .= "            \$table->index({$columnsStr}, '{$indexName}');\n";
                }

                $tableDown .= "            \$table->dropIndex('{$indexName}');\n";
            }

            $tableUp .= "        });\n";
            $tableDown .= "        });\n";

            $upMethods[] = $tableUp;
            $downMethods[] = $tableDown;
        }

        $upCode = implode("\n", $upMethods);
        $downCode = implode("\n", array_reverse($downMethods));

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
{$upCode}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
{$downCode}
    }
};
PHP;
    }
}
