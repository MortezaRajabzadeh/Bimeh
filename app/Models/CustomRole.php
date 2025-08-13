<?php

namespace App\Models;

use Spatie\Permission\Models\Role;

class CustomRole extends Role
{
    protected $table = 'roles';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'parent_id',
        'inherit_permissions',
        'guard_name',
    ];

    protected $casts = [
        'inherit_permissions' => 'boolean',
    ];

    /**
     * رابطه با والد
     */
    public function parent()
    {
        return $this->belongsTo(CustomRole::class, 'parent_id');
    }

    /**
     * رابطه با فرزندان
     */
    public function children()
    {
        return $this->hasMany(CustomRole::class, 'parent_id');
    }

    /**
     * تمام فرزندان (بازگشتی)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * گرفتن تمام اجداد
     */
    public function ancestors()
    {
        $ancestors = collect();
        $role = $this;

        while ($role->parent) {
            $ancestors->push($role->parent);
            $role = $role->parent;
        }

        return $ancestors;
    }

    /**
     * بررسی اینکه آیا این role فرزند role دیگری است
     */
    public function isChildOf(CustomRole $role)
    {
        return $this->ancestors()->contains('id', $role->id);
    }

    /**
     * گرفتن تمام مجوزها (شامل مجوزهای به ارث رسیده)
     */
    public function getInheritedPermissions()
    {
        $permissions = $this->permissions;

        if ($this->inherit_permissions && $this->parent) {
            $inheritedPermissions = $this->parent->getInheritedPermissions();
            $permissions = $permissions->merge($inheritedPermissions)->unique('id');
        }

        return $permissions;
    }

    /**
     * گرفتن عمق در درخت سلسله‌مراتب
     */
    public function getDepthAttribute()
    {
        return $this->ancestors()->count();
    }

    /**
     * گرفتن نام نمایشی یا نام اصلی
     */
    public function getDisplayNameAttribute($value)
    {
        return $value ?: $this->name;
    }

    /**
     * مجوزهای فارسی
     */
    public static function getPermissionLabels()
    {
        return [
            // مجوزهای عمومی و مشترک
            'view dashboard' => 'مشاهده داشبورد',
            'view profile' => 'مشاهده پروفایل',
            'edit profile' => 'ویرایش پروفایل',

            // مجوزهای گزارش‌ها
            'view basic reports' => 'مشاهده گزارش‌های پایه',
            'view advanced reports' => 'مشاهده گزارش‌های پیشرفته',
            'export reports' => 'خروجی گرفتن از گزارش‌ها',
            'view financial reports' => 'مشاهده گزارش‌های مالی',

            // مجوزهای خانواده
            'view all families' => 'مشاهده همه خانواده‌ها',
            'view own families' => 'مشاهده خانواده‌های خود',
            'create family' => 'ایجاد خانواده',
            'edit own family' => 'ویرایش خانواده خود',
            'edit any family' => 'ویرایش هر خانواده',
            'delete own family' => 'حذف خانواده خود',
            'delete any family' => 'حذف هر خانواده',
            'change family status' => 'تغییر وضعیت خانواده',
            'verify family' => 'تایید خانواده',
            'reject family' => 'رد خانواده',

            // مجوزهای اعضای خانواده
            'view family members' => 'مشاهده اعضای خانواده',
            'add family member' => 'افزودن عضو خانواده',
            'edit family member' => 'ویرایش عضو خانواده',
            'remove family member' => 'حذف عضو خانواده',

            // مجوزهای بیمه
            'manage insurance policies' => 'مدیریت پالیس‌های بیمه',
            'process claims' => 'پردازش درخواست‌های بیمه',
            'approve claims' => 'تایید درخواست‌های بیمه',
            'reject claims' => 'رد درخواست‌های بیمه',
            'view claims history' => 'مشاهده تاریخچه درخواست‌ها',
            'calculate premiums' => 'محاسبه حق بیمه',

            // مجوزهای سهم‌بندی بیمه
            'view insurance shares' => 'مشاهده سهم‌بندی بیمه',
            'manage insurance shares' => 'مدیریت سهم‌بندی بیمه',
            'create insurance shares' => 'ایجاد سهم‌بندی بیمه',
            'edit insurance shares' => 'ویرایش سهم‌بندی بیمه',
            'delete insurance shares' => 'حذف سهم‌بندی بیمه',

            // مجوزهای پرداخت
            'view insurance payments' => 'مشاهده پرداخت‌های بیمه',
            'manage insurance payments' => 'مدیریت پرداخت‌های بیمه',
            'create insurance payments' => 'ایجاد پرداخت بیمه',
            'edit insurance payments' => 'ویرایش پرداخت بیمه',
            'delete insurance payments' => 'حذف پرداخت بیمه',
            'view payment details' => 'مشاهده جزئیات پرداخت',
            'export payment reports' => 'خروجی گزارش پرداخت‌ها',

            // مجوزهای مدیریت سیستم
            'manage users' => 'مدیریت کاربران',
            'view users' => 'مشاهده کاربران',
            'create user' => 'ایجاد کاربر',
            'edit user' => 'ویرایش کاربر',
            'delete user' => 'حذف کاربر',
            'manage roles' => 'مدیریت نقش‌ها',
            'manage permissions' => 'مدیریت مجوزها',
            'view system logs' => 'مشاهده لاگ‌های سیستم',
            'manage system settings' => 'مدیریت تنظیمات سیستم',
            'view all statistics' => 'مشاهده تمام آمار',
            'manage regions' => 'مدیریت مناطق',
            'backup system' => 'پشتیبان‌گیری سیستم',
            'restore system' => 'بازیابی سیستم',

            // مجوزهای سازمان
            'manage organizations' => 'مدیریت سازمان‌ها',
            'view organizations' => 'مشاهده سازمان‌ها',
        ];
    }

    /**
     * گرفتن لیبل فارسی مجوز
     */
    public static function getPermissionLabel($permission)
    {
        $labels = self::getPermissionLabels();
        return $labels[$permission] ?? $permission;
    }
}
