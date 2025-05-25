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
        // این seeder برای تخصیص رول‌ها به کاربران موجود استفاده می‌شود
        // دسترسی‌ها قبلاً در PermissionSeeder تخصیص داده شده‌اند
        
        $this->command->info('🔄 بررسی تخصیص رول‌ها...');
        
        // اطمینان از وجود رول‌ها
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $charityRole = Role::firstOrCreate(['name' => 'charity']);
        $insuranceRole = Role::firstOrCreate(['name' => 'insurance']);

        // تخصیص رول‌ها به کاربران بر اساس user_type
        $usersUpdated = 0;
        
        User::all()->each(function ($user) use (&$usersUpdated, $adminRole, $charityRole, $insuranceRole) {
            $targetRole = null;
            
            switch ($user->user_type) {
                case 'admin':
                    $targetRole = $adminRole;
                    break;
                case 'charity':
                    $targetRole = $charityRole;
                    break;
                case 'insurance':
                    $targetRole = $insuranceRole;
                    break;
            }
            
            if ($targetRole && !$user->hasRole($targetRole->name)) {
                $user->assignRole($targetRole);
                $usersUpdated++;
                $this->command->info("✅ رول {$targetRole->name} به کاربر {$user->name} تخصیص داده شد.");
            }
        });

        if ($usersUpdated === 0) {
            $this->command->info('✅ همه کاربران قبلاً رول مناسب دارند.');
        } else {
            $this->command->info("✅ رول {$usersUpdated} کاربر بروزرسانی شد.");
        }
    }
} 