<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AssignRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // نقش‌ها
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $charityRole = Role::firstOrCreate(['name' => 'charity']);
        $insuranceRole = Role::firstOrCreate(['name' => 'insurance']);

        // دسترسی‌ها (نمونه)
        $allPermissions = Permission::all();
        $adminRole->syncPermissions($allPermissions);
        $charityRole->syncPermissions($allPermissions->where('name', 'like', 'charity%'));
        $insuranceRole->syncPermissions($allPermissions->where('name', 'like', 'insurance%'));

        // نسبت دادن نقش به کاربران
        foreach (User::all() as $user) {
            if ($user->user_type === 'admin') {
                $user->assignRole('admin');
            } elseif ($user->user_type === 'charity') {
                $user->assignRole('charity');
            } elseif ($user->user_type === 'insurance') {
                $user->assignRole('insurance');
            }
        }
    }
} 