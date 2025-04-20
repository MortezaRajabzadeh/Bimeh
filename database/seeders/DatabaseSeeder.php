<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ایجاد سازمان خیریه نمونه
        $charity = Organization::create([
            'name' => 'خیریه نمونه',
            'type' => 'charity',
            'code' => 'CH001',
            'phone' => '021-12345678',
            'email' => 'charity@example.com',
            'address' => 'تهران، خیابان انقلاب',
            'is_active' => true,
        ]);

        // ایجاد سازمان بیمه نمونه
        $insurance = Organization::create([
            'name' => 'بیمه نمونه',
            'type' => 'insurance',
            'code' => 'IN001',
            'phone' => '021-87654321',
            'email' => 'insurance@example.com',
            'address' => 'تهران، خیابان آزادی',
            'is_active' => true,
        ]);

        // ایجاد کاربر ادمین
        User::create([
            'username' => 'admin',
            'name' => 'مدیر سیستم',
            'email' => 'admin@example.com',
            'mobile' => '09121234567',
            'password' => Hash::make('password'),
            'user_type' => 'admin',
            'is_active' => true,
        ]);

        // ایجاد کاربر خیریه
        User::create([
            'username' => 'charity',
            'name' => 'کاربر خیریه',
            'email' => 'charity_user@example.com',
            'mobile' => '09123456789',
            'password' => Hash::make('password'),
            'user_type' => 'charity',
            'organization_id' => $charity->id,
            'is_active' => true,
        ]);

        // ایجاد کاربر بیمه
        User::create([
            'username' => 'insurance',
            'name' => 'کاربر بیمه',
            'email' => 'insurance_user@example.com',
            'mobile' => '09198765432',
            'password' => Hash::make('password'),
            'user_type' => 'insurance',
            'organization_id' => $insurance->id,
            'is_active' => true,
        ]);
        
        // اجرای سیدر خانواده‌ها
        $this->call([
            DefaultUsersSeeder::class,
            AdminUserSeeder::class,
            PermissionSeeder::class,
            OrganizationSeeder::class,
            RegionSeeder::class,
            FamilySeeder::class,
            CreateFamiliesSeeder::class,
        ]);
    }
}
