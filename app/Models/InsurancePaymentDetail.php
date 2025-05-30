<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InsurancePaymentDetail extends Model
{
    use HasFactory;

    /**
     * فیلدهای قابل پر شدن
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'insurance_payment_id',
        'member_id',
        'individual_amount',
        'insurance_type',
        'coverage_start_date',
        'coverage_end_date',
        'notes',
    ];

    /**
     * فیلدهای تبدیل به مقادیر داده‌ای
     *
     * @var array<string, string>
     */
    protected $casts = [
        'individual_amount' => 'decimal:2',
        'coverage_start_date' => 'date',
        'coverage_end_date' => 'date',
    ];

    /**
     * رابطه با پرداخت بیمه
     */
    public function insurancePayment()
    {
        return $this->belongsTo(InsurancePayment::class);
    }

    /**
     * رابطه با عضو خانواده
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * محاسبه مدت پوشش بیمه (به روز)
     */
    public function getCoverageDurationAttribute()
    {
        if ($this->coverage_start_date && $this->coverage_end_date) {
            return $this->coverage_start_date->diffInDays($this->coverage_end_date);
        }
        return 0;
    }

    /**
     * بررسی اینکه آیا پوشش بیمه فعال است
     */
    public function isActiveCoverage()
    {
        $now = now()->toDateString();
        return $this->coverage_start_date <= $now && $this->coverage_end_date >= $now;
    }

    /**
     * فیلتر پوشش‌های فعال
     */
    public function scopeActiveCoverage($query)
    {
        $now = now()->toDateString();
        return $query->where('coverage_start_date', '<=', $now)
                    ->where('coverage_end_date', '>=', $now);
    }

    /**
     * فیلتر بر اساس نوع بیمه
     */
    public function scopeByInsuranceType($query, $type)
    {
        return $query->where('insurance_type', $type);
    }
}
