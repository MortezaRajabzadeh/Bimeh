<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InsurancePayment extends Model
{
    use HasFactory, LogsActivity;

    /**
     * فیلدهای قابل پر شدن
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'payment_code',
        'family_insurance_id',
        'total_amount',
        'insured_persons_count',
        'payment_date',
        'payment_status',
        'payment_method',
        'transaction_reference',
        'description',
        'paid_by',
    ];

    /**
     * فیلدهای تبدیل به مقادیر داده‌ای
     *
     * @var array<string, string>
     */
    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_date' => 'date',
        'insured_persons_count' => 'integer',
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['payment_code', 'total_amount', 'payment_status', 'payment_date'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "پرداخت بیمه {$eventName} شد");
    }

    /**
     * رابطه با بیمه خانواده
     */
    public function familyInsurance()
    {
        return $this->belongsTo(FamilyInsurance::class);
    }

    /**
     * رابطه با کاربر پرداخت‌کننده
     */
    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    /**
     * رابطه با جزئیات پرداخت
     */
    public function details()
    {
        return $this->hasMany(InsurancePaymentDetail::class);
    }

    /**
     * ترجمه وضعیت پرداخت به فارسی
     */
    public function getPaymentStatusFaAttribute()
    {
        $statuses = [
            'pending' => 'در انتظار',
            'paid' => 'پرداخت شده',
            'failed' => 'ناموفق',
            'refunded' => 'بازگشت داده شده',
        ];

        return $statuses[$this->payment_status] ?? $this->payment_status;
    }

    /**
     * محاسبه مبلغ متوسط هر فرد
     */
    public function getAverageAmountPerPersonAttribute()
    {
        if ($this->insured_persons_count > 0) {
            return $this->total_amount / $this->insured_persons_count;
        }
        return 0;
    }

    /**
     * فیلتر پرداخت‌های موفق
     */
    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    /**
     * فیلتر پرداخت‌های در انتظار
     */
    public function scopePending($query)
    {
        return $query->where('payment_status', 'pending');
    }

    /**
     * فیلتر پرداخت‌های ناموفق
     */
    public function scopeFailed($query)
    {
        return $query->where('payment_status', 'failed');
    }

    /**
     * تولید کد پرداخت منحصر به فرد
     */
    public static function generatePaymentCode()
    {
        do {
            $code = 'PAY-' . now()->format('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));
        } while (static::where('payment_code', $code)->exists());

        return $code;
    }
}
