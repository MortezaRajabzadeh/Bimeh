<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ایجاد نقش‌های کاربری
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $charityRole = Role::firstOrCreate(['name' => 'charity']);
        $insuranceRole = Role::firstOrCreate(['name' => 'insurance']);

        // تعریف دسترسی‌های سازمان‌ها
        $organizationPermissions = [
            'view organizations',
            'create organization',
            'edit organization',
            'delete organization',
        ];

        // تعریف دسترسی‌های مناطق
        $regionPermissions = [
            'view regions',
            'create region',
            'edit region',
            'delete region',
        ];

        // تعریف دسترسی‌های کاربران
        $userPermissions = [
            'view users',
            'create user',
            'edit user',
            'delete user',
        ];

        // تعریف دسترسی‌های خانواده‌ها
        $familyPermissions = [
            'view families',
            'create family',
            'edit family',
            'delete family',
            'change family status',
            'verify family',
        ];

        // تعریف دسترسی‌های اعضای خانواده
        $memberPermissions = [
            'view members',
            'create member',
            'edit member',
            'delete member',
        ];

        // تعریف دسترسی‌های گزارش‌ها
        $reportPermissions = [
            'view reports',
            'export reports',
        ];

        // تجمیع همه دسترسی‌ها
        $allPermissions = array_merge(
            $organizationPermissions,
            $regionPermissions,
            $userPermissions,
            $familyPermissions,
            $memberPermissions,
            $reportPermissions,
            ['view dashboard', 'view activity logs']
        );

        // ایجاد دسترسی‌ها
        foreach($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // اختصاص دسترسی‌ها به ادمین
        $adminRole->givePermissionTo($allPermissions);

        // اختصاص دسترسی‌ها به کاربر خیریه
        $charityRole->givePermissionTo([
            'view families',
            'create family',
            'edit family',
            'view members',
            'create member',
            'edit member',
            'delete member',
            'view regions',
            'view dashboard',
        ]);

        // اختصاص دسترسی‌ها به کاربر بیمه
        $insuranceRole->givePermissionTo([
            'view families',
            'change family status',
            'verify family',
            'view members',
            'view regions',
            'view dashboard',
            'view reports',
            'export reports',
        ]);
    }
}