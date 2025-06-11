<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CheckRankingTables extends Command
{
    protected $signature = 'check:ranking-tables';
    protected $description = 'Check if ranking related tables exist in the database';

    public function handle()
    {
        $this->info('Checking for ranking related tables...');
        
        // Get all tables
        $tables = DB::select('SHOW TABLES');
        $dbName = DB::getDatabaseName();
        $key = 'Tables_in_' . $dbName;
        
        $tableNames = array_map(function($table) use ($key) {
            return $table->$key;
        }, $tables);
        
        // Check for ranking related tables
        $rankingTables = [
            'ranking_schemes',
            'ranking_scheme_criteria',
            'rank_settings',
            'family_criteria',
            'rank_setting_family',
        ];
        
        $foundTables = [];
        foreach ($rankingTables as $table) {
            $exists = in_array($table, $tableNames) ? '✅' : '❌';
            $foundTables[] = [$table, $exists];
        }
        
        $this->table(['Table Name', 'Exists'], $foundTables);
        
        // If ranking_schemes exists, show some data
        if (in_array('ranking_schemes', $tableNames)) {
            $this->info('\nRanking Schemes:');
            $schemes = DB::table('ranking_schemes')->get();
            if ($schemes->isEmpty()) {
                $this->warn('No ranking schemes found in the database.');
            } else {
                $this->table(
                    ['ID', 'Name', 'Description', 'User ID', 'Created At'],
                    $schemes->toArray()
                );
            }
        }
        
        // If ranking_scheme_criteria exists, show some data
        if (in_array('ranking_scheme_criteria', $tableNames)) {
            $this->info('\nRanking Scheme Criteria:');
            $criteria = DB::table('ranking_scheme_criteria')->get();
            if ($criteria->isEmpty()) {
                $this->warn('No ranking scheme criteria found in the database.');
            } else {
                $this->table(
                    ['ID', 'Ranking Scheme ID', 'Rank Setting ID', 'Weight', 'Created At'],
                    $criteria->toArray()
                );
            }
        }
        
        // If rank_settings exists, show some data
        if (in_array('rank_settings', $tableNames)) {
            $this->info('\nRank Settings:');
            $settings = DB::table('rank_settings')->get();
            if ($settings->isEmpty()) {
                $this->warn('No rank settings found in the database.');
            } else {
                $this->table(
                    ['ID', 'Name', 'Key', 'Weight', 'Category', 'Is Active'],
                    $settings->map(function($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'key' => $item->key,
                            'weight' => $item->weight,
                            'category' => $item->category,
                            'is_active' => $item->is_active ? 'Yes' : 'No',
                        ];
                    })->toArray()
                );
            }
        }
        
        return 0;
    }
}
