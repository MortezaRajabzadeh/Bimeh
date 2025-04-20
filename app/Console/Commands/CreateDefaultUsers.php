<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateDefaultUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-default-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ایجاد کاربران پیش‌فرض سیستم (مدیر، خیریه و بیمه)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('شروع ایجاد کاربران پیش‌فرض سیستم...');

        // بررسی و ایجاد سازمان خیریه
        $charity = Organization::where('type', 'charity')->first();
        if (!$charity) {
            $charity = new Organization();
            $charity->name = 'خیریه نمونه';
            $charity->type = 'charity';
            $charity->is_active = true;
            $charity->save();
            $this->info('سازمان خیریه ایجاد شد.');
        }

        // بررسی و ایجاد سازمان بیمه
        $insurance = Organization::where('type', 'insurance')->first();
        if (!$insurance) {
            $insurance = new Organization();
            $insurance->name = 'سازمان بیمه نمونه';
            $insurance->type = 'insurance';
            $insurance->is_active = true;
            $insurance->save();
            $this->info('سازمان بیمه ایجاد شد.');
        }

        // ایجاد کاربر مدیر سیستم
        $admin = User::where('email', 'admin@example.com')->first();
        if (!$admin) {
            User::create([
                'name' => 'مدیر سیستم',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'user_type' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $this->info('کاربر مدیر سیستم ایجاد شد.');
        } else {
            $this->info('کاربر مدیر سیستم قبلاً ایجاد شده است.');
        }

        // ایجاد کاربر خیریه
        $charityUser = User::where('email', 'charity@example.com')->first();
        if (!$charityUser) {
            User::create([
                'name' => 'کاربر خیریه',
                'username' => 'charity_user',
                'email' => 'charity@example.com',
                'password' => Hash::make('password'),
                'user_type' => 'charity',
                'organization_id' => $charity->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $this->info('کاربر خیریه ایجاد شد.');
        } else {
            $this->info('کاربر خیریه قبلاً ایجاد شده است.');
        }

        // ایجاد کاربر بیمه
        $insuranceUser = User::where('email', 'insurance@example.com')->first();
        if (!$insuranceUser) {
            User::create([
                'name' => 'کاربر بیمه',
                'username' => 'insurance_user',
                'email' => 'insurance@example.com',
                'password' => Hash::make('password'),
                'user_type' => 'insurance',
                'organization_id' => $insurance->id,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
            $this->info('کاربر بیمه ایجاد شد.');
        } else {
            $this->info('کاربر بیمه قبلاً ایجاد شده است.');
        }

        $this->info('تمام کاربران پیش‌فرض با موفقیت ایجاد شدند.');
        $this->info('نام کاربری/ایمیل و رمز عبور یکسان برای تمام کاربران: password');
        
        return 0;
    }
}
