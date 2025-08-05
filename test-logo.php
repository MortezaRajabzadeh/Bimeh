<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// تست Organization ID 12
$organization = App\Models\Organization::find(12);

if ($organization) {
    echo "Organization ID: " . $organization->id . "\n";
    echo "Logo Path: " . $organization->logo_path . "\n";
    echo "Logo URL: " . $organization->logo_url . "\n";
    echo "Direct URL: " . config('app.url') . '/storage/' . $organization->logo_path . "\n";
    
    // تست وجود فایل
    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($organization->logo_path)) {
        echo "File exists in storage\n";
    } else {
        echo "File does not exist in storage\n";
    }
    
    // تست وجود فایل در public
    $publicPath = 'public/storage/' . $organization->logo_path;
    if (file_exists($publicPath)) {
        echo "File exists in public: " . $publicPath . "\n";
    } else {
        echo "File does not exist in public: " . $publicPath . "\n";
    }
} else {
    echo "Organization not found\n";
} 