<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Family extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    /**
     * فیلدهای قابل پر شدن
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'family_code',
        'province_id',
        'city_id',
        'district_id',
        'region_id',
        'region',
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
        'is_insured' => 'boolean',
    ];

    /**
     * تنظیمات مدیا لایبرری
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('family_photos')
            ->singleFile()
            ->useDisk('public');
    }

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'poverty_confirmed', 'verified_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "خانواده {$eventName} شد");
    }

    /**
     * رابطه با سازمان معرف (خیریه)
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'charity_id');
    }

    /**
     * رابطه با استان
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * رابطه با شهر
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * رابطه با دهستان
     */
    public function district()
    {
        return $this->belongsTo(District::class);
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
    public function membersWithInsurance()
    {
        return $this->hasMany(Member::class)->whereNotNull('insurance_type');
    }
    /**
     * رابطه با اعضای خانواده
     */
    public function members()
    {
        return $this->hasMany(\App\Models\Member::class);
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
        return $this->hasOne(\App\Models\Member::class)->where('is_head', true);
    }

    public function region()
    {
        return $this->belongsTo(\App\Models\Region::class, 'region_id');
    }

    /**
     * کوئری برای خانواده‌هایی که بیمه‌شان منقضی شده و هنوز renewal نشده‌اند
     */
    public function scopeExpiredInsurance($query)
    {
        return $query->whereHas('insurances', function ($q) {
            $q->whereNotNull('insurance_end_date')
              ->where('insurance_end_date', '<', now());
        })->where('status', '!=', 'renewal');
    }

    public function insurances()
    {
        return $this->hasMany(FamilyInsurance::class);
    }

    /**
     * تعداد بیمه‌های این خانواده
     */
    public function insuranceCount()
    {
        return $this->insurances()->count();
    }

    /**
     * لیست انواع بیمه‌های این خانواده
     */
    public function insuranceTypes()
    {
        return $this->insurances()->pluck('insurance_type')->unique();
    }

    /**
     * لیست پرداخت‌کنندگان حق بیمه این خانواده
     */
    public function insurancePayers()
    {
        return $this->insurances()->pluck('insurance_payer')->unique();
    }
} 