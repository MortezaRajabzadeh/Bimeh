@props(['permission'])

@php
    $permissionTranslations = [
        // مدیریت سیستم (ادمین)
        'manage users' => 'مدیریت کاربران',
        'manage organizations' => 'مدیریت سازمان‌ها',
        'manage roles' => 'مدیریت رول‌ها',
        'manage permissions' => 'مدیریت دسترسی‌ها',
        'view system logs' => 'مشاهده لاگ‌های سیستم',
        'manage system settings' => 'مدیریت تنظیمات سیستم',
        'view all statistics' => 'مشاهده تمام آمار',
        'manage regions' => 'مدیریت مناطق',
        'backup system' => 'پشتیبان‌گیری سیستم',
        'restore system' => 'بازیابی سیستم',

        // دسترسی‌های مشترک
        'view dashboard' => 'مشاهده داشبورد',
        'view profile' => 'مشاهده پروفایل',
        'edit profile' => 'ویرایش پروفایل',

        // دسترسی‌های خانواده‌ها
        'view own families' => 'مشاهده خانواده‌های خودی',
        'view all families' => 'مشاهده همه خانواده‌ها',
        'create family' => 'ایجاد خانواده',
        'edit own family' => 'ویرایش خانواده خودی',
        'edit any family' => 'ویرایش هر خانواده',
        'delete own family' => 'حذف خانواده خودی',
        'delete any family' => 'حذف هر خانواده',
        'change family status' => 'تغییر وضعیت خانواده',
        'verify family' => 'تایید خانواده',
        'reject family' => 'رد خانواده',

        // دسترسی‌های اعضای خانواده
        'view family members' => 'مشاهده اعضای خانواده',
        'add family member' => 'افزودن عضو خانواده',
        'edit family member' => 'ویرایش عضو خانواده',
        'remove family member' => 'حذف عضو خانواده',

        // دسترسی‌های گزارش‌ها
        'view basic reports' => 'مشاهده گزارش‌های پایه',
        'view advanced reports' => 'مشاهده گزارش‌های پیشرفته',
        'export reports' => 'خروجی گرفتن از گزارش‌ها',
        'view financial reports' => 'مشاهده گزارش‌های مالی',

        // دسترسی‌های بیمه
        'manage insurance policies' => 'مدیریت پالیس‌های بیمه',
        'process claims' => 'پردازش ادعاها',
        'approve claims' => 'تایید ادعاها',
        'reject claims' => 'رد ادعاها',
        'view claims history' => 'مشاهده تاریخچه ادعاها',
        'calculate premiums' => 'محاسبه حق بیمه',

        // ترجمه‌های قدیمی (برای سازگاری)
        'edit user' => 'ویرایش کاربر',
        'view users' => 'مشاهده کاربران',
        'delete user' => 'حذف کاربر',
        'edit region' => 'ویرایش منطقه',
        'create region' => 'ایجاد منطقه',
        'view regions' => 'مشاهده مناطق',
        'delete region' => 'حذف منطقه',
        'edit organization' => 'ویرایش سازمان',
        'create organization' => 'ایجاد سازمان',
        'view organizations' => 'مشاهده سازمان‌ها',
        'delete organization' => 'حذف سازمان',
        'edit family' => 'ویرایش خانواده',
        'view families' => 'مشاهده خانواده‌ها',
        'delete family' => 'حذف خانواده',
        'edit member' => 'ویرایش عضو',
        'create member' => 'ایجاد عضو',
        'view members' => 'مشاهده اعضا',
        'delete member' => 'حذف عضو',
        'view reports' => 'مشاهده گزارش‌ها',
        'view activity logs' => 'مشاهده لاگ‌های فعالیت',
    ];
    
    $translation = isset($permissionTranslations[$permission]) 
        ? $permissionTranslations[$permission] 
        : $permission;
@endphp

{{ $translation }} 