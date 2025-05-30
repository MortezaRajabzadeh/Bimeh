# 🔐 راهنمای سیستم رول‌ها و دسترسی‌ها - میکروبیمه

## 📋 معرفی

سیستم میکروبیمه از پکیج `spatie/laravel-permission` برای مدیریت رول‌ها و دسترسی‌ها استفاده می‌کند. این سیستم شامل سه رول اصلی است:

- **👑 Admin (مدیر سیستم)**: دسترسی کامل به تمام بخش‌ها
- **🏥 Charity (خیریه)**: مدیریت خانواده‌ها و درخواست‌های بیمه
- **🛡️ Insurance (بیمه)**: بررسی و تایید درخواست‌های بیمه

## 🎯 رول‌ها و دسترسی‌ها

### 👑 Admin (مدیر سیستم)
**دسترسی کامل به:**
- `manage users` - مدیریت کاربران
- `manage organizations` - مدیریت سازمان‌ها
- `manage roles` - مدیریت رول‌ها
- `manage permissions` - مدیریت دسترسی‌ها
- `view system logs` - مشاهده لاگ‌های سیستم
- `manage system settings` - تنظیمات سیستم
- `view all statistics` - مشاهده تمام آمار
- `export reports` - خروجی گزارش‌ها
- `manage regions` - مدیریت مناطق
- `view dashboard` - مشاهده داشبورد

### 🏥 Charity (خیریه)
**دسترسی به:**
- `view families` - مشاهده خانواده‌ها
- `create family` - ایجاد خانواده جدید
- `edit family` - ویرایش اطلاعات خانواده
- `delete family` - حذف خانواده
- `manage family members` - مدیریت اعضای خانواده
- `submit insurance request` - ارسال درخواست بیمه
- `view own statistics` - مشاهده آمار خود
- `export own reports` - خروجی گزارش‌های خود

### 🛡️ Insurance (بیمه)
**دسترسی به:**
- `view families` - مشاهده خانواده‌ها
- `review insurance requests` - بررسی درخواست‌های بیمه
- `approve insurance` - تایید بیمه
- `reject insurance` - رد بیمه
- `view insurance statistics` - مشاهده آمار بیمه
- `export insurance reports` - خروجی گزارش‌های بیمه

## 🔧 نصب و راه‌اندازی

### 1. اجرای Migrations
```bash
php artisan migrate
```

### 2. اجرای Seeders
```bash
php artisan db:seed
```

### 3. ایجاد کاربران پیش‌فرض
```bash
php artisan users:create-defaults
```

## 👥 کاربران پیش‌فرض

| نوع کاربر | ایمیل | رمز عبور | رول |
|-----------|-------|----------|-----|
| مدیر سیستم | admin@microbime.com | Admin@123456 | admin |
| خیریه | charity@microbime.com | Charity@123456 | charity |
| بیمه | insurance@microbime.com | Insurance@123456 | insurance |

## 🛡️ Middleware ها

### CheckUserType
```php
Route::middleware(['auth', 'user.type:admin'])->group(function () {
    // Routes فقط برای ادمین
});
```

### Spatie Permission Middleware
```php
// بررسی رول
Route::middleware(['role:admin'])->group(function () {
    // Routes فقط برای رول admin
});

// بررسی دسترسی
Route::middleware(['permission:manage users'])->group(function () {
    // Routes فقط برای کسانی که دسترسی manage users دارند
});

// بررسی رول یا دسترسی
Route::middleware(['role_or_permission:admin|manage users'])->group(function () {
    // Routes برای ادمین یا کسانی که دسترسی manage users دارند
});
```

## 📁 ساختار فایل‌ها

```
app/
├── Http/
│   ├── Middleware/
│   │   └── CheckUserType.php          # Middleware بررسی نوع کاربر
│   └── Kernel.php                     # تعریف middleware ها
├── Console/
│   └── Commands/
│       └── CreateDefaultUsers.php     # کامند ایجاد کاربران پیش‌فرض
└── Models/
    └── User.php                       # مدل کاربر با trait HasRoles

database/
├── migrations/
│   ├── 2024_01_01_000000_create_laravel_core_tables.php
│   └── ...
└── seeders/
    ├── PermissionSeeder.php           # ایجاد رول‌ها و دسترسی‌ها
    ├── RoleSeeder.php                 # ایجاد رول‌های اضافی
    ├── AssignRolePermissionSeeder.php # تخصیص رول‌ها
    └── DatabaseSeeder.php             # اجرای تمام seeders

routes/
└── web.php                           # تعریف routes با middleware ها
```

## 🔍 نحوه استفاده در کد

### بررسی رول در Controller
```php
public function index()
{
    if (auth()->user()->hasRole('admin')) {
        // کد مخصوص ادمین
    }
    
    if (auth()->user()->can('manage users')) {
        // کد مخصوص کسانی که دسترسی manage users دارند
    }
}
```

### بررسی رول در Blade
```blade
@role('admin')
    <p>شما ادمین هستید</p>
@endrole

@can('manage users')
    <a href="{{ route('admin.users.index') }}">مدیریت کاربران</a>
@endcan

@hasrole('charity')
    <p>شما کاربر خیریه هستید</p>
@endhasrole
```

### بررسی رول در Livewire
```php
class FamilyWizard extends Component
{
    public function mount()
    {
        if (!auth()->user()->hasRole('charity')) {
            abort(403);
        }
    }
    
    public function render()
    {
        return view('livewire.charity.family-wizard');
    }
}
```

## 🔄 مدیریت رول‌ها و دسترسی‌ها

### اضافه کردن رول جدید
```php
use Spatie\Permission\Models\Role;

$role = Role::create(['name' => 'new_role']);
```

### اضافه کردن دسترسی جدید
```php
use Spatie\Permission\Models\Permission;

$permission = Permission::create(['name' => 'new_permission']);
```

### تخصیص دسترسی به رول
```php
$role = Role::findByName('charity');
$role->givePermissionTo('new_permission');
```

### تخصیص رول به کاربر
```php
$user = User::find(1);
$user->assignRole('charity');
```

## 🚨 نکات مهم

1. **ترتیب Middleware**: همیشه `auth` را قبل از `role` یا `permission` قرار دهید
2. **Cache**: spatie/permission از cache استفاده می‌کند، پس از تغییرات cache را پاک کنید:
   ```bash
   php artisan permission:cache-reset
   ```
3. **Migration**: هرگز جداول permissions و roles را دستی تغییر ندهید
4. **Seeding**: همیشه PermissionSeeder را قبل از AssignRolePermissionSeeder اجرا کنید

## 🔧 عیب‌یابی

### مشکلات رایج:

1. **خطای "Permission does not exist"**
   ```bash
   php artisan permission:cache-reset
   php artisan db:seed --class=PermissionSeeder
   ```

2. **کاربر دسترسی ندارد**
   ```bash
   php artisan users:create-defaults
   ```

3. **Middleware کار نمی‌کند**
   - بررسی کنید که middleware در `Kernel.php` تعریف شده باشد
   - مطمئن شوید که route در گروه middleware صحیح قرار دارد

## 📞 پشتیبانی

برای مشکلات مربوط به سیستم رول‌ها و دسترسی‌ها:

1. ابتدا cache را پاک کنید: `php artisan permission:cache-reset`
2. seeders را مجدداً اجرا کنید: `php artisan db:seed`
3. کاربران پیش‌فرض را ایجاد کنید: `php artisan users:create-defaults`

---

**📅 آخرین بروزرسانی:** {{ date('Y-m-d') }}  
**🔗 مستندات spatie/permission:** https://spatie.be/docs/laravel-permission 