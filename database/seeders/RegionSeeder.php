<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // لیست استان‌ها و شهرهای ایران
        $regions = [
            [
                'name' => 'منطقه ۱ تهران',
                'province' => 'تهران',
                'city' => 'تهران',
                'description' => 'شمال تهران شامل مناطق ولنجک، نیاوران و...',
                'is_active' => true
            ],
            [
                'name' => 'منطقه ۲ تهران',
                'province' => 'تهران',
                'city' => 'تهران',
                'description' => 'غرب تهران شامل سعادت آباد، شهرک غرب و...',
                'is_active' => true
            ],
            [
                'name' => 'منطقه ۳ تهران',
                'province' => 'تهران',
                'city' => 'تهران',
                'description' => 'شمال و شمال‌شرق تهران شامل ونک، زعفرانیه و...',
                'is_active' => true
            ],
            [
                'name' => 'منطقه ۴ تهران',
                'province' => 'تهران',
                'city' => 'تهران',
                'description' => 'شرق تهران شامل تهرانپارس، حکیمیه و...',
                'is_active' => true
            ],
            [
                'name' => 'منطقه ۵ تهران',
                'province' => 'تهران',
                'city' => 'تهران',
                'description' => 'شمال‌غرب تهران شامل پونک، جنت‌آباد و...',
                'is_active' => true
            ],
            [
                'name' => 'شیراز - منطقه ۱',
                'province' => 'فارس',
                'city' => 'شیراز',
                'description' => 'مرکز شیراز شامل زند، ملاصدرا و...',
                'is_active' => true
            ],
            [
                'name' => 'اصفهان - منطقه ۱',
                'province' => 'اصفهان',
                'city' => 'اصفهان',
                'description' => 'مرکز اصفهان',
                'is_active' => true
            ],
            [
                'name' => 'مشهد - منطقه ۱',
                'province' => 'خراسان رضوی',
                'city' => 'مشهد',
                'description' => 'منطقه مرکزی مشهد',
                'is_active' => true
            ],
            [
                'name' => 'تبریز - منطقه ۱',
                'province' => 'آذربایجان شرقی',
                'city' => 'تبریز',
                'description' => 'منطقه مرکزی تبریز',
                'is_active' => true
            ],
        ];
        
        foreach ($regions as $region) {
            Region::create($region);
        }
    }
} 