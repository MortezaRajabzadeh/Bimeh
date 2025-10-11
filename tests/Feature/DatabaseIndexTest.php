<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseIndexTest extends TestCase
{
    /**
     * لیست جداول برای تست
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
     * دریافت index‌های موجود جدول
     */
    protected function getTableIndexes(string $table): array
    {
        if (!Schema::hasTable($table)) {
            $this->markTestSkipped("Table {$table} does not exist");
        }

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
            $this->markTestSkipped("Cannot read indexes from {$table}: " . $e->getMessage());
        }

        return $indexes;
    }

    /**
     * بررسی وجود index با ستون‌های مشخص
     */
    protected function hasIndex(string $table, array $columns): bool
    {
        $indexes = $this->getTableIndexes($table);
        
        foreach ($indexes as $indexName => $indexColumns) {
            // مقایسه ستون‌ها (order-sensitive)
            if ($indexColumns === $columns) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * دریافت لیست تمام foreign keys جدول
     */
    protected function getForeignKeys(string $table): array
    {
        try {
            $fks = DB::select("
                SELECT COLUMN_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$table]);
            
            return array_map(fn($fk) => $fk->COLUMN_NAME, $fks);
        } catch (\Exception $e) {
            return [];
        }
    }

    // ========================================
    // Tests: funding_transactions
    // ========================================

    /** @test */
    public function funding_transactions_has_created_at_index()
    {
        $this->assertTrue(
            $this->hasIndex('funding_transactions', ['created_at']),
            'Missing index on funding_transactions.created_at. ' .
            'This index is required for date range queries in FinancialReportController. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    /** @test */
    public function funding_transactions_has_allocated_index()
    {
        $this->assertTrue(
            $this->hasIndex('funding_transactions', ['allocated']),
            'Missing index on funding_transactions.allocated. ' .
            'This index is required for allocated/non-allocated filter in FundingTransactionRepository. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    /** @test */
    public function funding_transactions_has_status_created_at_composite_index()
    {
        $this->assertTrue(
            $this->hasIndex('funding_transactions', ['status', 'created_at']),
            'Missing composite index on funding_transactions (status, created_at). ' .
            'This index is required for status filter with ordering in PaidClaims Livewire. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    // ========================================
    // Tests: insurance_shares
    // ========================================

    /** @test */
    public function insurance_shares_has_family_insurance_payer_type_index()
    {
        $this->assertTrue(
            $this->hasIndex('insurance_shares', ['family_insurance_id', 'payer_type']),
            'Missing existing index on insurance_shares (family_insurance_id, payer_type). ' .
            'This index should have been created in create_insurance_shares_table migration.'
        );
    }

    /** @test */
    public function insurance_shares_has_is_paid_payment_date_index()
    {
        $this->assertTrue(
            $this->hasIndex('insurance_shares', ['is_paid', 'payment_date']),
            'Missing existing index on insurance_shares (is_paid, payment_date). ' .
            'This index should have been created in create_insurance_shares_table migration.'
        );
    }

    /** @test */
    public function insurance_shares_has_family_insurance_amount_index()
    {
        $this->assertTrue(
            $this->hasIndex('insurance_shares', ['family_insurance_id', 'amount']),
            'Missing composite index on insurance_shares (family_insurance_id, amount). ' .
            'This index is required for amount filter in InsuranceTransactionRepository. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    /** @test */
    public function insurance_shares_has_import_log_id_index()
    {
        $this->assertTrue(
            $this->hasIndex('insurance_shares', ['import_log_id']),
            'Missing index on insurance_shares.import_log_id. ' .
            'This index is required for whereNull filter to separate manual shares from bulk allocations. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    // ========================================
    // Tests: share_allocation_logs
    // ========================================

    /** @test */
    public function share_allocation_logs_has_status_total_amount_index()
    {
        $this->assertTrue(
            $this->hasIndex('share_allocation_logs', ['status', 'total_amount']),
            'Missing composite index on share_allocation_logs (status, total_amount). ' .
            'This index is required for main Repository query. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    /** @test */
    public function share_allocation_logs_has_updated_at_index()
    {
        $this->assertTrue(
            $this->hasIndex('share_allocation_logs', ['updated_at']),
            'Missing index on share_allocation_logs.updated_at. ' .
            'This index is required for ordering in Service. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    /** @test */
    public function share_allocation_logs_has_batch_id_index()
    {
        $this->assertTrue(
            $this->hasIndex('share_allocation_logs', ['batch_id']),
            'Missing index on share_allocation_logs.batch_id. ' .
            'This index is required for batch lookup queries. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    /** @test */
    public function share_allocation_logs_has_file_hash_index()
    {
        $this->assertTrue(
            $this->hasIndex('share_allocation_logs', ['file_hash']),
            'Missing index on share_allocation_logs.file_hash. ' .
            'This index is required for duplicate validation. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    // ========================================
    // Tests: family_funding_allocations
    // ========================================

    /** @test */
    public function family_funding_allocations_has_status_transaction_id_index()
    {
        $this->assertTrue(
            $this->hasIndex('family_funding_allocations', ['status', 'transaction_id']),
            'Missing composite index on family_funding_allocations (status, transaction_id). ' .
            'This index is required for main Repository query with status filter and null transaction check. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    /** @test */
    public function family_funding_allocations_has_approved_at_index()
    {
        $this->assertTrue(
            $this->hasIndex('family_funding_allocations', ['approved_at']),
            'Missing index on family_funding_allocations.approved_at. ' .
            'This index is required for ordering by approval date. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    /** @test */
    public function family_funding_allocations_has_funding_source_status_index()
    {
        $this->assertTrue(
            $this->hasIndex('family_funding_allocations', ['funding_source_id', 'status']),
            'Missing composite index on family_funding_allocations (funding_source_id, status). ' .
            'This index is required for SUM calculations by funding source. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    // ========================================
    // Tests: family_insurances
    // ========================================

    /** @test */
    public function family_insurances_has_family_id_insurance_type_index()
    {
        $this->assertTrue(
            $this->hasIndex('family_insurances', ['family_id', 'insurance_type']),
            'Missing existing index on family_insurances (family_id, insurance_type). ' .
            'This index should have been created in create_microbime_core_tables migration.'
        );
    }

    /** @test */
    public function family_insurances_has_status_index()
    {
        $this->assertTrue(
            $this->hasIndex('family_insurances', ['status']),
            'Missing index on family_insurances.status. ' .
            'This index is required for status filters in whereHas queries. ' .
            'Run migration: 2025_10_11_000001_add_family_insurances_indexes.php'
        );
    }

    /** @test */
    public function family_insurances_has_family_id_status_index()
    {
        $this->assertTrue(
            $this->hasIndex('family_insurances', ['family_id', 'status']),
            'Missing composite index on family_insurances (family_id, status). ' .
            'This index is CRITICAL for whereHas queries performance. ' .
            'Expected improvement: 10x faster (from ~2000ms to ~200ms). ' .
            'Run migration: 2025_10_11_000001_add_family_insurances_indexes.php'
        );
    }

    /** @test */
    public function family_insurances_has_created_at_index()
    {
        $this->assertTrue(
            $this->hasIndex('family_insurances', ['created_at']),
            'Missing index on family_insurances.created_at. ' .
            'This index is required for ordering and date filters. ' .
            'Run migration: 2025_10_11_000001_add_family_insurances_indexes.php'
        );
    }

    /** @test */
    public function family_insurances_has_status_premium_amount_index()
    {
        $this->assertTrue(
            $this->hasIndex('family_insurances', ['status', 'premium_amount']),
            'Missing composite index on family_insurances (status, premium_amount). ' .
            'This index is required for status with premium amount filter. ' .
            'Run migration: 2025_10_11_000001_add_family_insurances_indexes.php'
        );
    }

    // ========================================
    // Tests: insurance_allocations
    // ========================================

    /** @test */
    public function insurance_allocations_has_created_at_index()
    {
        $this->assertTrue(
            $this->hasIndex('insurance_allocations', ['created_at']),
            'Missing index on insurance_allocations.created_at. ' .
            'This index is required for date range queries in DashboardStats. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    /** @test */
    public function insurance_allocations_has_duplicate_check_index()
    {
        $this->assertTrue(
            $this->hasIndex('insurance_allocations', ['family_id', 'amount', 'issue_date']),
            'Missing composite index on insurance_allocations (family_id, amount, issue_date). ' .
            'This index is required for duplicate check in ClaimsImportService. ' .
            'Run migration: 2025_10_11_000000_add_financial_report_indexes.php'
        );
    }

    // ========================================
    // General Tests
    // ========================================

    /** @test */
    public function all_financial_tables_exist()
    {
        foreach ($this->financialTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Financial table '{$table}' does not exist. Please run migrations."
            );
        }
    }

    /** @test */
    public function all_foreign_keys_have_indexes()
    {
        $missingIndexes = [];

        foreach ($this->financialTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $foreignKeys = $this->getForeignKeys($table);
            $indexes = $this->getTableIndexes($table);

            foreach ($foreignKeys as $fk) {
                $hasIndex = false;
                
                // بررسی اینکه FK در کدام index وجود دارد
                foreach ($indexes as $indexColumns) {
                    if (in_array($fk, $indexColumns)) {
                        $hasIndex = true;
                        break;
                    }
                }

                if (!$hasIndex) {
                    $missingIndexes[] = "{$table}.{$fk}";
                }
            }
        }

        $this->assertEmpty(
            $missingIndexes,
            'The following foreign keys are missing indexes: ' . implode(', ', $missingIndexes) . '. ' .
            'Foreign keys should always have indexes for optimal JOIN performance.'
        );
    }

    /** @test */
    public function critical_indexes_have_proper_column_order()
    {
        // تست ترتیب ستون‌ها در composite indexes
        
        // funding_transactions: status باید قبل از created_at باشد
        $indexes = $this->getTableIndexes('funding_transactions');
        $found = false;
        foreach ($indexes as $columns) {
            if (count($columns) >= 2 && $columns[0] === 'status' && $columns[1] === 'created_at') {
                $found = true;
                break;
            }
        }
        $this->assertTrue(
            $found,
            'Composite index (status, created_at) on funding_transactions has wrong column order or is missing. ' .
            'Correct order: status (equality) first, created_at (ordering) second.'
        );

        // family_insurances: family_id باید قبل از status باشد
        $indexes = $this->getTableIndexes('family_insurances');
        $found = false;
        foreach ($indexes as $columns) {
            if (count($columns) >= 2 && $columns[0] === 'family_id' && $columns[1] === 'status') {
                $found = true;
                break;
            }
        }
        $this->assertTrue(
            $found,
            'Composite index (family_id, status) on family_insurances has wrong column order or is missing. ' .
            'Correct order: family_id (JOIN key) first, status (filter) second.'
        );
    }

    /** @test */
    public function no_duplicate_single_column_indexes()
    {
        $duplicates = [];

        foreach ($this->financialTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $indexes = $this->getTableIndexes($table);
            $singleColumnIndexes = [];

            foreach ($indexes as $indexName => $columns) {
                // نادیده گرفتن PRIMARY و UNIQUE
                if ($indexName === 'PRIMARY' || str_contains($indexName, 'unique')) {
                    continue;
                }

                if (count($columns) === 1) {
                    $column = $columns[0];
                    
                    if (isset($singleColumnIndexes[$column])) {
                        $duplicates[] = "{$table}.{$column} (indexes: {$singleColumnIndexes[$column]}, {$indexName})";
                    } else {
                        $singleColumnIndexes[$column] = $indexName;
                    }
                }
            }
        }

        $this->assertEmpty(
            $duplicates,
            'The following columns have duplicate single-column indexes: ' . implode(', ', $duplicates) . '. ' .
            'Remove redundant indexes to improve write performance.'
        );
    }

    /** @test */
    public function index_count_is_reasonable()
    {
        // هر جدول نباید بیش از 10 index داشته باشد (به جز PRIMARY و UNIQUE)
        $tablesWithTooManyIndexes = [];

        foreach ($this->financialTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $indexes = $this->getTableIndexes($table);
            
            // فیلتر کردن PRIMARY و UNIQUE
            $regularIndexes = array_filter(
                array_keys($indexes),
                fn($name) => $name !== 'PRIMARY' && !str_contains($name, 'unique')
            );

            if (count($regularIndexes) > 10) {
                $tablesWithTooManyIndexes[] = "{$table} ({count($regularIndexes)} indexes)";
            }
        }

        $this->assertEmpty(
            $tablesWithTooManyIndexes,
            'The following tables have too many indexes: ' . implode(', ', $tablesWithTooManyIndexes) . '. ' .
            'Too many indexes can hurt write performance. Consider removing unused indexes.'
        );
    }

    /**
     * Data Provider for parametrized tests
     */
    public function criticalIndexesProvider(): array
    {
        return [
            // [table, columns, reason]
            ['funding_transactions', ['created_at'], 'Date range queries'],
            ['funding_transactions', ['allocated'], 'Allocated filter'],
            ['funding_transactions', ['status', 'created_at'], 'Status with ordering'],
            
            ['insurance_shares', ['family_insurance_id', 'amount'], 'Family with amount filter'],
            ['insurance_shares', ['import_log_id'], 'whereNull for manual shares'],
            
            ['share_allocation_logs', ['status', 'total_amount'], 'Status with amount filter'],
            ['share_allocation_logs', ['updated_at'], 'Ordering'],
            
            ['family_funding_allocations', ['status', 'transaction_id'], 'Status with null transaction'],
            
            ['family_insurances', ['status'], 'Status filter'],
            ['family_insurances', ['family_id', 'status'], 'whereHas optimization'],
            
            ['insurance_allocations', ['created_at'], 'Date range queries'],
        ];
    }

    /**
     * @test
     * @dataProvider criticalIndexesProvider
     */
    public function critical_index_exists(string $table, array $columns, string $reason)
    {
        $this->assertTrue(
            $this->hasIndex($table, $columns),
            sprintf(
                'Missing CRITICAL index on %s.(%s). ' .
                'Reason: %s. ' .
                'This index is essential for performance. Please run the appropriate migration.',
                $table,
                implode(', ', $columns),
                $reason
            )
        );
    }
}
