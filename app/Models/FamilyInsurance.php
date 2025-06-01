<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FamilyInsurance extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'family_id', 
        'insurance_type', 
        'insurance_payer',
        'premium_amount',
        'start_date', 
        'end_date',
        'family_code',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'premium_amount' => 'float',
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['insurance_type', 'insurance_payer', 'premium_amount', 'start_date', 'end_date'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "بیمه خانواده {$eventName} شد");
    }

    /**
     * رابطه با خانواده
     */
    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * رابطه با سهم‌های بیمه
     */
    public function shares()
    {
        return $this->hasMany(InsuranceShare::class);
    }

    /**
     * محاسبه مجموع درصد سهم‌ها
     */
    public function getTotalSharePercentage()
    {
        return $this->shares()->sum('percentage');
    }

    /**
     * محاسبه مجموع مبلغ سهم‌ها
     */
    public function getTotalShareAmount()
    {
        return $this->shares()->sum('amount');
    }

    /**
     * بررسی اینکه آیا تمام سهم‌ها پرداخت شده‌اند
     */
    public function areAllSharesPaid()
    {
        return $this->shares()->where('is_paid', false)->count() === 0;
    }

    /**
     * دریافت سهم‌های پرداخت نشده
     */
    public function getUnpaidShares()
    {
        return $this->shares()->where('is_paid', false)->get();
    }

    /**
     * دریافت سهم‌های پرداخت شده
     */
    public function getPaidShares()
    {
        return $this->shares()->where('is_paid', true)->get();
    }

    /**
     * بررسی اینکه آیا بیمه منقضی شده است
     */
    public function isExpired()
    {
        return $this->end_date && $this->end_date < now();
    }

    /**
     * بررسی اینکه آیا بیمه فعال است
     */
    public function isActive()
    {
        return $this->start_date <= now() && (!$this->end_date || $this->end_date >= now());
    }
} 