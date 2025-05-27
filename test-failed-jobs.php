<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->boot();

echo "Failed jobs count: " . \Illuminate\Support\Facades\DB::table('failed_jobs')->count() . "\n";

$latestFailed = \Illuminate\Support\Facades\DB::table('failed_jobs')->latest('id')->first();
if ($latestFailed) {
    echo "Latest failed job exception:\n";
    echo substr($latestFailed->exception, 0, 1000) . "\n";
} else {
    echo "No failed jobs\n";
} 