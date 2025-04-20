<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ایجاد سازمان خیریه
        Organization::create([
            'name' => 'خیریه نیکوکاران',
            'type' => 'charity',
            'code' => 'CH001',
            'phone' => '+98 21 1234567',
            'email' => 'charity@example.com',
            'address' => 'تهران، خیابان ولیعصر',
            'description' => 'سازمان خیریه نیکوکاران',
            'is_active' => true,
        ]);

        // ایجاد سازمان بیمه
        Organization::create([
            'name' => 'بیمه سلامت',
            'type' => 'insurance',
            'code' => 'INS001',
            'phone' => '+98 21 7654321',
            'email' => 'insurance@example.com',
            'address' => 'تهران، خیابان پاسداران',
            'description' => 'سازمان بیمه سلامت',
            'is_active' => true,
        ]);
    }
} 