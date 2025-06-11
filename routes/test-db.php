<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;

Route::get('/test-db', function () {
    // Check if ranking_schemes table exists
    $tables = DB::select('SHOW TABLES');
    $dbName = DB::getDatabaseName();
    $key = 'Tables_in_' . $dbName;
    
    $tableNames = array_map(function($table) use ($key) {
        return $table->$key;
    }, $tables);
    
    $rankingTables = array_filter($tableNames, function($table) {
        return str_contains(strtolower($table), 'rank') || str_contains(strtolower($table), 'scheme');
    });
    
    $rankingSchemesExists = in_array('ranking_schemes', $tables);
    $schemeCriteriaExists = in_array('ranking_scheme_criteria', $tables);
    
    return [
        'all_tables' => $tableNames,
        'ranking_related_tables' => array_values($rankingTables),
        'ranking_schemes_exists' => $rankingSchemesExists,
        'ranking_scheme_criteria_exists' => $schemeCriteriaExists,
        'rank_settings_exists' => in_array('rank_settings', $tables),
    ];
});
