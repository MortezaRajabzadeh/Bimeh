<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CreateDefaultUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:create-defaults';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ایجاد کاربران پیش‌فرض سیستم و تخصیص رول‌ها';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('شروع ایجاد کاربران پیش‌فرض...');

        // ایجاد سازمان‌های پیش‌فرض
        $charity = Organization::firstOrCreate([
            'type' => 'charity',
            'name' => 'خیریه نمونه',
        ], [
            'code' => 'CHR001',
            'phone' => '021-12345678',
            'email' => 'info@charity.example.com',
            'address' => 'تهران، خیابان ولیعصر',
            'is_active' => true,
        ]);

        $insurance = Organization::firstOrCreate([
            'type' => 'insurance',
            'name' => 'شرکت بیمه نمونه',
        ], [
            'code' => 'INS001',
            'phone' => '021-87654321',
            'email' => 'info@insurance.example.com',
            'address' => 'تهران، خیابان کریمخان',
            'is_active' => true,
        ]);

        // ایجاد رول‌ها
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $charityRole = Role::firstOrCreate(['name' => 'charity']);
        $insuranceRole = Role::firstOrCreate(['name' => 'insurance']);

        // ایجاد کاربر ادمین
        $admin = User::where('email', 'admin@microbime.com')->orWhere('username', 'admin')->first();
        if (!$admin) {
            $admin = User::create([
                'name' => 'مدیر سیستم',
                'username' => 'admin_new',
                'email' => 'admin@microbime.com',
                'password' => Hash::make('Admin@123456'),
                'user_type' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $admin->assignRole('admin');
            $this->info('✅ کاربر مدیر سیستم ایجاد شد.');
        } else {
            // آپدیت کاربر موجود
            $admin->update([
                'email' => 'admin@microbime.com',
                'user_type' => 'admin',
                'is_active' => true,
            ]);
            if (!$admin->hasRole('admin')) {
                $admin->assignRole('admin');
            }
            $this->info('✅ کاربر مدیر سیستم آپدیت شد.');
        }

        // ایجاد کاربر خیریه
        $charityUser = User::where('email', 'charity@microbime.com')->first();
        if (!$charityUser) {
            $charityUser = User::create([
                'name' => 'کاربر خیریه',
                'username' => 'charity_user',
                'email' => 'charity@microbime.com',
                'password' => Hash::make('Charity@123456'),
                'user_type' => 'charity',
                'organization_id' => $charity->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $charityUser->assignRole('charity');
            $this->info('✅ کاربر خیریه ایجاد شد.');
        } else {
            if (!$charityUser->hasRole('charity')) {
                $charityUser->assignRole('charity');
            }
            $this->info('✅ کاربر خیریه قبلاً ایجاد شده است.');
        }

        // ایجاد کاربر بیمه
        $insuranceUser = User::where('email', 'insurance@microbime.com')->first();
        if (!$insuranceUser) {
            $insuranceUser = User::create([
                'name' => 'کاربر بیمه',
                'username' => 'insurance_user',
                'email' => 'insurance@microbime.com',
                'password' => Hash::make('Insurance@123456'),
                'user_type' => 'insurance',
                'organization_id' => $insurance->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $insuranceUser->assignRole('insurance');
            $this->info('✅ کاربر بیمه ایجاد شد.');
        } else {
            if (!$insuranceUser->hasRole('insurance')) {
                $insuranceUser->assignRole('insurance');
            }
            $this->info('✅ کاربر بیمه قبلاً ایجاد شده است.');
        }

        // تخصیص رول به کاربران موجود بر اساس user_type
        $this->info('🔄 بررسی و تخصیص رول‌ها به کاربران موجود...');
        
        $usersUpdated = 0;
        User::all()->each(function ($user) use (&$usersUpdated) {
            $expectedRole = null;
            
            switch ($user->user_type) {
                case 'admin':
                    $expectedRole = 'admin';
                    break;
                case 'charity':
                    $expectedRole = 'charity';
                    break;
                case 'insurance':
                    $expectedRole = 'insurance';
                    break;
            }
            
            if ($expectedRole && !$user->hasRole($expectedRole)) {
                $user->assignRole($expectedRole);
                $usersUpdated++;
                $this->info("✅ رول {$expectedRole} به کاربر {$user->name} تخصیص داده شد.");
            }
        });

        if ($usersUpdated === 0) {
            $this->info('✅ همه کاربران قبلاً رول مناسب دارند.');
        } else {
            $this->info("✅ رول {$usersUpdated} کاربر بروزرسانی شد.");
        }

        $this->info('🎉 عملیات با موفقیت تکمیل شد!');
        
        $this->newLine();
        $this->info('📋 اطلاعات لاگین:');
        $this->table(['نوع کاربر', 'ایمیل', 'رمز عبور'], [
            ['مدیر سیستم', 'admin@microbime.com', 'Admin@123456'],
            ['خیریه', 'charity@microbime.com', 'Charity@123456'],
            ['بیمه', 'insurance@microbime.com', 'Insurance@123456'],
        ]);
    }
}
