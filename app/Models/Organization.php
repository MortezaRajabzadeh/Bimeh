<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Organization extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * فیلدهای قابل پر شدن
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'type',
        'code',
        'phone',
        'email',
        'address',
        'logo_path',
        'description',
        'is_active',
    ];

    /**
     * فیلدهای تبدیل به مقادیر داده‌ای
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'code', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "سازمان {$eventName} شد");
    }

    /**
     * رابطه با کاربران
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * رابطه با خانواده‌ها (برای خیریه‌ها)
     */
    public function registeredFamilies()
    {
        return $this->hasMany(Family::class, 'charity_id');
    }

    /**
     * رابطه با خانواده‌ها (برای بیمه‌ها)
     */
    public function insuredFamilies()
    {
        return $this->hasMany(Family::class, 'insurance_id');
    }

    /**
     * رابطه با اعضای معرفی شده توسط این سازمان
     */
    public function members()
    {
        return $this->hasMany(Member::class, 'charity_id');
    }

    /**
     * فیلتر سازمان‌های بیمه
     */
    public function scopeInsurance($query)
    {
        return $query->where('type', 'insurance');
    }

    /**
     * فیلتر سازمان‌های خیریه
     */
    public function scopeCharity($query)
    {
        return $query->where('type', 'charity');
    }

    /**
     * فیلتر سازمان‌های فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the logo URL attribute.
     */
    /**
     * Get the full URL to the organization's logo.
     *
     * @return string
     */
    public function getLogoUrlAttribute()
    {
        return $this->attributes['logo_path'] ? Storage::url($this->attributes['logo_path']) : null;
    }
    
    /**
     * Get the logo path with storage disk
     * 
     * @return string|null
     */
    public function getLogoPathAttribute()
    {
        return $this->attributes['logo_path'] ? Storage::url($this->attributes['logo_path']) : null;
    }

    /**
     * آپلود و بهینه‌سازی لوگوی سازمان
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string|null
     */
    public function uploadLogo($file)
    {
        if (!$file) {
            return null;
        }

        // حذف لوگوی قبلی اگر وجود دارد
        if ($this->logo_path && Storage::exists($this->logo_path)) {
            Storage::delete($this->logo_path);
        }

        $filename = 'org-' . uniqid() . '.' . $file->getClientOriginalExtension();
        $path = 'organizations/logos/' . $filename;

        // ایجاد دایرکتوری اگر وجود نداشته باشد
        if (!Storage::exists('organizations/logos')) {
            Storage::makeDirectory('organizations/logos');
        }

        // بهینه‌سازی و ذخیره تصویر
        $image = Image::read($file)
            ->cover(200, 200) // تغییر سایز به 200x200 پیکسل
            ->toWebp(75); // تبدیل به فرمت webp با کیفیت 75%


        Storage::put($path, $image);

        return $path;
    }

    /**
     * حذف لوگوی سازمان
     */
    public function deleteLogo()
    {
        if ($this->logo_path && Storage::exists($this->logo_path)) {
            Storage::delete($this->logo_path);
            $this->logo_path = null;
            $this->save();
        }
    }
}
