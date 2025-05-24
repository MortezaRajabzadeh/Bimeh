<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TestController extends Controller
{
    public function testUserModel()
    {
        // بررسی وجود فیلد national_code در جدول users
        $hasColumn = Schema::hasColumn('users', 'national_code');
        
        // بررسی اولین کاربر
        $user = User::first();
        
        // تست ذخیره national_code
        $saveSuccess = false;
        if ($user) {
            try {
                $originalValue = $user->national_code;
                $testValue = 'test-' . time();
                $user->national_code = $testValue;
                $user->save();
                
                // بازیابی مجدد برای بررسی ذخیره صحیح
                $refreshedUser = User::find($user->id);
                $saveSuccess = ($refreshedUser->national_code === $testValue);
                
                // برگرداندن به مقدار اصلی
                $user->national_code = $originalValue;
                $user->save();
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }
        
        // نمایش ساختار جدول users
        try {
            $tableStructure = DB::select('DESCRIBE users');
        } catch (\Exception $e) {
            $tableStructure = $e->getMessage();
        }
        
        $result = [
            'has_national_code_column' => $hasColumn,
            'user_found' => (bool)$user,
            'save_success' => $saveSuccess,
            'error' => $error ?? null,
            'table_structure' => $tableStructure,
            'model_fillable' => User::make()->getFillable(),
        ];
        
        // ذخیره نتایج تست در فایل لاگ برای بررسی بعدی
        \Illuminate\Support\Facades\Log::info('User Model Test Results', $result);
        
        // خروجی به صورت JSON برای نمایش در مرورگر
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit;
    }
    
    public function testAdminPermissions()
    {
        $adminUser = User::where('user_type', 'admin')->first();
        if (!$adminUser) {
            return ['error' => 'No admin user found'];
        }
        
        return [
            'user' => [
                'id' => $adminUser->id,
                'name' => $adminUser->name,
                'username' => $adminUser->username,
                'email' => $adminUser->email,
                'user_type' => $adminUser->user_type,
            ],
            'roles' => $adminUser->getRoleNames(),
            'permissions' => $adminUser->getAllPermissions()->pluck('name'),
            'specific_permissions' => [
                'view_users' => $adminUser->can('view users'),
                'create_user' => $adminUser->can('create user'),
                'edit_user' => $adminUser->can('edit user'),
                'delete_user' => $adminUser->can('delete user'),
            ]
        ];
    }
}
