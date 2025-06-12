<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    public function getLogoUrlAttribute()
    {
        if ($this->logo_path) {
            return asset('storage/' . $this->logo_path);
        }
        
        return asset('images/default-organization.png');
    }
} 
