<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // سطوح دسترسی اصلی سیستم
        $roles = [
            'admin' => 'ادمین',
            'charity' => 'خیریه',
            'insurance' => 'بیمه',
        ];

        // ایجاد یا بروزرسانی سطوح دسترسی
        foreach ($roles as $role => $description) {
            Role::firstOrCreate(['name' => $role], [
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }

        // اطمینان از دسترسی کامل برای ادمین
        $adminRole = Role::findByName('admin');
        $allPermissions = Permission::all();
        $adminRole->syncPermissions($allPermissions);
    }
} 