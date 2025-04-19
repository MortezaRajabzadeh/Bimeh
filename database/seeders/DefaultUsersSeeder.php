<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;

class DefaultUsersSeeder extends Seeder
{
    /**
     * اجرای سیدر کاربران پیش‌فرض
     */
    public function run(): void
    {
        // ایجاد سازمان‌های نمونه
        $charity = Organization::firstOrCreate(
            ['name' => 'خیریه نمونه'],
            [
                'address' => 'تهران، خیابان مطهری',
                'phone' => '02112345678',
                'email' => 'charity@example.com',
                'type' => 'charity',
                'is_active' => true
            ]
        );
        
        $insurance = Organization::firstOrCreate(
            ['name' => 'بیمه نمونه'],
            [
                'address' => 'تهران، خیابان ولیعصر',
                'phone' => '02187654321',
                'email' => 'insurance@example.com',
                'type' => 'insurance',
                'is_active' => true
            ]
        );
        
        // ایجاد کاربر مدیر سیستم
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'مدیر سیستم',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'organization_id' => null,
                'user_type' => 'admin',
                'is_active' => true,
                'mobile' => '09123456789'
            ]
        );
        
        // ایجاد کاربر خیریه
        User::firstOrCreate(
            ['email' => 'charity@example.com'],
            [
                'name' => 'کاربر خیریه',
                'username' => 'charity',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'organization_id' => $charity->id,
                'user_type' => 'charity',
                'is_active' => true,
                'mobile' => '09123456780'
            ]
        );
        
        // ایجاد کاربر بیمه
        User::firstOrCreate(
            ['email' => 'insurance@example.com'],
            [
                'name' => 'کاربر بیمه',
                'username' => 'insurance',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'organization_id' => $insurance->id,
                'user_type' => 'insurance',
                'is_active' => true,
                'mobile' => '09123456781'
            ]
        );
        
        $this->command->info('کاربران پیش‌فرض با موفقیت ایجاد شدند.');
    }
}
