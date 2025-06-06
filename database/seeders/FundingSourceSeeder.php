<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FundingSource;

class FundingSourceSeeder extends Seeder
{
    /**
     * منابع مالی پیش‌فرض را ایجاد می‌کند
     */
    public function run()
    {
        $sources = [
            [
                'name' => 'خیریه',
                'type' => 'charity',
                'description' => 'منبع مالی خیریه',
                'is_active' => true,
            ],
            [
                'name' => 'دولتی',
                'type' => 'government',
                'description' => 'منبع مالی دولتی',
                'is_active' => true,
            ],
            [
                'name' => 'بانک',
                'type' => 'bank',
                'description' => 'منبع مالی بانک',
                'is_active' => true,
            ],
            [
                'name' => 'شخص حقیقی',
                'type' => 'person',
                'description' => 'منبع مالی شخص حقیقی',
                'is_active' => true,
            ],
            [
                'name' => 'سایر',
                'type' => 'other',
                'description' => 'سایر منابع مالی',
                'is_active' => true,
            ],
        ];

        foreach ($sources as $source) {
            // بررسی وجود منبع مالی با همین نام
            $exists = FundingSource::where('name', $source['name'])->exists();
            
            if (!$exists) {
                FundingSource::create($source);
                $this->command->info("منبع مالی '{$source['name']}' اضافه شد.");
            } else {
                $this->command->info("منبع مالی '{$source['name']}' از قبل وجود دارد.");
            }
        }
    }
} 