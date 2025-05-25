<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ترتیب اجرای seeders مهم است
        $this->call([
            // ابتدا permissions و roles
            PermissionSeeder::class,
            RoleSeeder::class,
            
            // سپس seeders مربوط به کاربران و سازمان‌ها
            AssignRolePermissionSeeder::class,
        ]);
        
        $this->command->info('✅ همه seeders با موفقیت اجرا شدند.');
        $this->command->info('💡 برای ایجاد کاربران پیش‌فرض، دستور زیر را اجرا کنید:');
        $this->command->info('php artisan users:create-defaults');
    }
} 