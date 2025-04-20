<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Family extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * فیلدهای قابل پر شدن
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'family_code',
        'region_id',
        'charity_id',
        'insurance_id',
        'registered_by',
        'address',
        'postal_code',
        'housing_status',
        'housing_description',
        'status',
        'rejection_reason',
        'poverty_confirmed',
        'additional_info',
        'verified_at',
        'is_insured',
        'acceptance_criteria',
    ];

    /**
     * فیلدهای تبدیل به مقادیر داده‌ای
     *
     * @var array<string, string>
     */
    protected $casts = [
        'poverty_confirmed' => 'boolean',
        'verified_at' => 'datetime',
        'acceptance_criteria' => 'array',
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['family_code', 'status', 'poverty_confirmed', 'verified_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "خانواده {$eventName} شد");
    }

    /**
     * رابطه با منطقه
     */
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    /**
     * رابطه با سازمان خیریه
     */
    public function charity()
    {
        return $this->belongsTo(Organization::class, 'charity_id');
    }

    /**
     * رابطه با سازمان بیمه
     */
    public function insurance()
    {
        return $this->belongsTo(Organization::class, 'insurance_id');
    }

    /**
     * رابطه با کاربر ثبت‌کننده
     */
    public function registeredByUser()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /**
     * رابطه با اعضای خانواده
     */
    public function members()
    {
        return $this->hasMany(Member::class);
    }

    /**
     * کوئری برای فیلتر کردن خانواده‌های در انتظار بررسی
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * کوئری برای فیلتر کردن خانواده‌های در حال بررسی
     */
    public function scopeReviewing($query)
    {
        return $query->where('status', 'reviewing');
    }

    /**
     * کوئری برای فیلتر کردن خانواده‌های تایید شده
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * کوئری برای فیلتر کردن خانواده‌های رد شده
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * دریافت سرپرست خانوار
     */
    public function head()
    {
        return $this->members()->where('is_head', true)->first();
    }
} 