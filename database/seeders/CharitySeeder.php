<?php

namespace Database\Seeders;

use App\Models\Charity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CharitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $charities = [
            [
                'name' => 'مهرآفرینان',
                'slug' => 'mehr-afarinan',
                'description' => 'خیریه حمایت از کودکان و زنان آسیب‌پذیر',
                'is_active' => true
            ],
            [
                'name' => 'محک',
                'slug' => 'mahak',
                'description' => 'مؤسسه خیریه حمایت از کودکان مبتلا به سرطان',
                'is_active' => true
            ],
            [
                'name' => 'کودکان کار',
                'slug' => 'working-children',
                'description' => 'خیریه حمایت از کودکان کار و خیابان',
                'is_active' => true
            ],
            [
                'name' => 'نیکوکاران شریف',
                'slug' => 'nikoukaran',
                'description' => 'مؤسسه خیریه حمایت از اقشار آسیب‌پذیر',
                'is_active' => true
            ],
            [
                'name' => 'خیریه امام علی (ع)',
                'slug' => 'imam-ali',
                'description' => 'جمعیت امام علی - امداد دانشجویی مردمی',
                'is_active' => true
            ],
        ];
        
        foreach ($charities as $charity) {
            Charity::updateOrCreate(
                ['slug' => $charity['slug']],
                $charity
            );
        }
    }
}
