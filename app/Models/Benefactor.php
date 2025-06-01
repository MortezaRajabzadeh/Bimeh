<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Benefactor extends Model
{
    /** @use HasFactory<\Database\Factories\BenefactorFactory> */
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'total_contributed',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'total_contributed' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'phone', 'total_contributed', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "نیکوکار {$eventName} شد");
    }

    /**
     * رابطه با منابع مالی
     */
    public function fundingSources()
    {
        return $this->hasMany(FundingSource::class);
    }

    /**
     * رابطه با تخصیصات بودجه از طریق منابع مالی
     */
    public function allocations()
    {
        return $this->hasManyThrough(FamilyFundingAllocation::class, FundingSource::class);
    }

    /**
     * اضافه کردن مبلغ به مجموع مشارکت
     */
    public function addContribution($amount)
    {
        $this->total_contributed += $amount;
        $this->save();
        
        return $this;
    }

    /**
     * فیلتر نیکوکاران فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * فیلتر نیکوکاران با مشارکت
     */
    public function scopeWithContributions($query)
    {
        return $query->where('total_contributed', '>', 0);
    }

    /**
     * دریافت تعداد خانواده‌هایی که از این نیکوکار کمک گرفته‌اند
     */
    public function getHelpedFamiliesCount()
    {
        return $this->allocations()
            ->where('status', FamilyFundingAllocation::STATUS_APPROVED)
            ->distinct('family_id')
            ->count();
    }
}
