<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SystemPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // مجوزهای داشبورد
            'view dashboard',
            'view statistics',

            // مجوزهای خانواده
            'view own families',
            'view all families', 
            'create family',
            'edit own family',
            'edit all families',
            'delete own family',
            'delete all families',
            'change family status',

            // مجوزهای اعضای خانواده
            'view family members',
            'add family member',
            'edit family member',
            'remove family member',

            // مجوزهای بیمه
            'manage insurance policies',
            'view insurance shares',
            'manage insurance shares',
            'view insurance payments',
            'manage insurance payments',

            // مجوزهای گزارش‌گیری
            'view basic reports',
            'view advanced reports',
            'export reports',
            'view financial reports',

            // مجوزهای مدیریت کاربران
            'manage users',
            'view user activities',
            'manage user permissions',

            // مجوزهای مدیریت نقش‌ها
            'manage roles',
            'assign roles',
            'manage permissions',

            // مجوزهای مدیریت سازمان‌ها
            'manage organizations',
            'view organization details',

            // مجوزهای سیستم
            'manage system settings',
            'view system logs',
            'backup system',

            // مجوزهای خاص
            'view profile',
            'edit profile',
            'view claims history',
            'manage claims',
        ];

        // ایجاد مجوزها
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ایجاد نقش‌های پایه
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $charityRole = Role::firstOrCreate(['name' => 'charity']);
        $insuranceRole = Role::firstOrCreate(['name' => 'insurance']);

        // تخصیص تمام مجوزها به ادمین
        $adminRole->syncPermissions(Permission::all());

        // مجوزهای خیریه
        $charityPermissions = [
            'view dashboard',
            'view statistics',
            'view own families',
            'create family',
            'edit own family',
            'delete own family',
            'view family members',
            'add family member',
            'edit family member',
            'remove family member',
            'view basic reports',
            'export reports',
            'view profile',
            'edit profile',
        ];
        
        $charityRole->syncPermissions($charityPermissions);

        // مجوزهای بیمه
        $insurancePermissions = [
            'view dashboard',
            'view statistics',
            'view all families',
            'change family status',
            'view family members',
            'manage insurance policies',
            'view insurance shares',
            'manage insurance shares',
            'view insurance payments',
            'manage insurance payments',
            'view advanced reports',
            'view financial reports',
            'export reports',
            'view profile',
            'edit profile',
            'view claims history',
            'manage claims',
        ];
        
        $insuranceRole->syncPermissions($insurancePermissions);

        $this->command->info('Permissions and roles seeded successfully!');
    }
}
