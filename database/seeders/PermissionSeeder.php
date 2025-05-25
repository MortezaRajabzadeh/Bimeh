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

        // تعریف دسترسی‌های مدیریت سیستم (فقط ادمین)
        $adminPermissions = [
            'manage users',
            'manage organizations', 
            'manage roles',
            'manage permissions',
            'view system logs',
            'manage system settings',
            'view all statistics',
            'manage regions',
            'backup system',
            'restore system',
        ];

        // تعریف دسترسی‌های مشترک
        $commonPermissions = [
            'view dashboard',
            'view profile',
            'edit profile',
        ];

        // تعریف دسترسی‌های خانواده‌ها
        $familyPermissions = [
            'view own families',      // مشاهده خانواده‌های خودی
            'view all families',      // مشاهده همه خانواده‌ها (ادمین + بیمه)
            'create family',          // ایجاد خانواده (خیریه + ادمین)
            'edit own family',        // ویرایش خانواده خودی
            'edit any family',        // ویرایش هر خانواده (ادمین)
            'delete own family',      // حذف خانواده خودی
            'delete any family',      // حذف هر خانواده (ادمین)
            'change family status',   // تغییر وضعیت خانواده (بیمه + ادمین)
            'verify family',          // تایید خانواده (بیمه + ادمین)
            'reject family',          // رد خانواده (بیمه + ادمین)
        ];

        // تعریف دسترسی‌های اعضای خانواده
        $memberPermissions = [
            'view family members',
            'add family member',
            'edit family member',
            'remove family member',
        ];

        // تعریف دسترسی‌های گزارش‌ها
        $reportPermissions = [
            'view basic reports',     // گزارش‌های پایه (خیریه)
            'view advanced reports',  // گزارش‌های پیشرفته (بیمه + ادمین)
            'export reports',         // خروجی گرفتن از گزارش‌ها
            'view financial reports', // گزارش‌های مالی (ادمین)
        ];

        // تعریف دسترسی‌های بیمه
        $insurancePermissions = [
            'manage insurance policies',
            'process claims',
            'approve claims',
            'reject claims',
            'view claims history',
            'calculate premiums',
        ];

        // تجمیع همه دسترسی‌ها
        $allPermissions = array_merge(
            $adminPermissions,
            $commonPermissions,
            $familyPermissions,
            $memberPermissions,
            $reportPermissions,
            $insurancePermissions
        );

        // ایجاد دسترسی‌ها
        foreach($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // پاک کردن تمام دسترسی‌های قبلی
        $adminRole->permissions()->detach();
        $charityRole->permissions()->detach();
        $insuranceRole->permissions()->detach();

        // اختصاص دسترسی‌ها به ادمین (همه دسترسی‌ها)
        $adminRole->givePermissionTo($allPermissions);

        // اختصاص دسترسی‌ها به کاربر خیریه
        $charityPermissions = array_merge($commonPermissions, [
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
        ]);
        $charityRole->givePermissionTo($charityPermissions);

        // اختصاص دسترسی‌ها به کاربر بیمه  
        $insurancePermissionsList = array_merge($commonPermissions, [
            'view all families',
            'change family status',
            'verify family',
            'reject family',
            'view family members',
            'view advanced reports',
            'export reports',
            'manage insurance policies',
            'process claims',
            'approve claims',
            'reject claims',
            'view claims history',
            'calculate premiums',
        ]);
        $insuranceRole->givePermissionTo($insurancePermissionsList);
    }
}