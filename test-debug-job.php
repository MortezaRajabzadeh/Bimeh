<?php

echo "Testing ProcessFamiliesImport Job directly...\n";

// تنظیم Laravel environment
putenv('APP_ENV=local');
require 'vendor/autoload.php';

try {
    $app = require 'bootstrap/app.php';
    
    // User and district
    $user = \App\Models\User::whereHas('roles', function($q) { 
        $q->where('name', 'charity'); 
    })->first();
    
    if (!$user) {
        echo "No charity user found!\n";
        exit;
    }
    
    $district = \App\Models\District::first();
    if (!$district) {
        echo "No district found!\n";
        exit;
    }
    
    echo "User: " . $user->email . "\n";
    echo "District: " . $district->name . "\n";
    
    // اجرای job به صورت مستقیم
    $job = new \App\Jobs\ProcessFamiliesImport($user, $district->id, 'test.xlsx', 'test.xlsx');
    
    // ایجاد fake file برای تست
    $testData = [
        ['nam' => 'احمد', 'nam_khanoadgy' => 'احمدی', 'kd_mly' => '1234567890'],
        ['nam' => 'فاطمه', 'nam_khanoadgy' => 'احمدی', 'kd_mly' => '1234567891'],
    ];
    
    echo "Running job directly...\n";
    $job->handle();
    echo "Job completed successfully!\n";
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
} 