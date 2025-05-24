@props(['permission'])

@php
    $permissionTranslations = [
        // کاربران
        'edit user' => 'ویرایش کاربر',
        'create user' => 'ایجاد کاربر',
        'view users' => 'مشاهده کاربران',
        'delete user' => 'حذف کاربر',

        // مناطق
        'edit region' => 'ویرایش منطقه',
        'create region' => 'ایجاد منطقه',
        'view regions' => 'مشاهده مناطق',
        'delete region' => 'حذف منطقه',

        // سازمان‌ها
        'edit organization' => 'ویرایش سازمان',
        'create organization' => 'ایجاد سازمان',
        'view organizations' => 'مشاهده سازمان‌ها',
        'delete organization' => 'حذف سازمان',

        // خانواده‌ها
        'edit family' => 'ویرایش خانواده',
        'create family' => 'ایجاد خانواده',
        'view families' => 'مشاهده خانواده‌ها',
        'delete family' => 'حذف خانواده',
        'verify family' => 'تایید خانواده',
        'change family status' => 'تغییر وضعیت خانواده',

        // اعضا
        'edit member' => 'ویرایش عضو',
        'create member' => 'ایجاد عضو',
        'view members' => 'مشاهده اعضا',
        'delete member' => 'حذف عضو',

        // گزارش‌ها
        'view reports' => 'مشاهده گزارش‌ها',
        'export reports' => 'خروجی گزارش‌ها',

        // داشبورد
        'view dashboard' => 'مشاهده داشبورد',

        // لاگ‌ها
        'view activity logs' => 'مشاهده لاگ‌ها',
    ];
    $translation = isset($permissionTranslations[$permission]) 
        ? $permissionTranslations[$permission] 
        : $permission;
@endphp

{{ $translation }} 