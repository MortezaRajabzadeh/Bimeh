<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ایجاد کاربر ادمین اولیه
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'username' => 'admin',
                'name' => 'مدیر سیستم',
                'password' => Hash::make('password'),
                'user_type' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // اختصاص نقش ادمین
        $admin->assignRole('admin');
    }
}