<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InsuranceShare extends Model
{
    use HasFactory, LogsActivity;

    /**
     * فیلدهای قابل پر شدن
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'family_insurance_id',
        'percentage',
        'payer_type',
        'payer_name',
        'payer_organization_id',
        'payer_user_id',
        'amount',
        'description',
        'is_paid',
        'payment_date',
        'payment_reference',
    ];

    /**
     * فیلدهای تبدیل به مقادیر داده‌ای
     *
     * @var array<string, string>
     */
    protected $casts = [
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'payment_date' => 'date',
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['percentage', 'payer_type', 'payer_name', 'amount', 'is_paid'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "سهم بیمه {$eventName} شد");
    }

    /**
     * رابطه با بیمه خانواده
     */
    public function familyInsurance()
    {
        return $this->belongsTo(FamilyInsurance::class);
    }

    /**
     * رابطه با سازمان پرداخت‌کننده
     */
    public function payerOrganization()
    {
        return $this->belongsTo(Organization::class, 'payer_organization_id');
    }

    /**
     * رابطه با کاربر پرداخت‌کننده
     */
    public function payerUser()
    {
        return $this->belongsTo(User::class, 'payer_user_id');
    }

    /**
     * ترجمه نوع پرداخت‌کننده به فارسی
     */
    public function getPayerTypeFaAttribute()
    {
        $types = [
            'insurance_company' => 'شرکت بیمه',
            'charity' => 'خیریه',
            'bank' => 'بانک',
            'government' => 'دولت',
            'individual_donor' => 'فرد خیر',
            'csr_budget' => 'بودجه CSR',
            'other' => 'سایر',
        ];

        return $types[$this->payer_type] ?? $this->payer_type;
    }

    /**
     * محاسبه مبلغ بر اساس درصد و مبلغ کل حق بیمه
     */
    public function calculateAmount()
    {
        if ($this->familyInsurance && $this->familyInsurance->premium_amount) {
            $this->amount = ($this->percentage / 100) * $this->familyInsurance->premium_amount;
            return $this->amount;
        }
        return 0;
    }

    /**
     * فیلتر سهم‌های پرداخت شده
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * فیلتر سهم‌های پرداخت نشده
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * فیلتر بر اساس نوع پرداخت‌کننده
     */
    public function scopeByPayerType($query, $type)
    {
        return $query->where('payer_type', $type);
    }

    /**
     * مجموع درصد سهم‌ها برای یک بیمه خانواده
     */
    public static function getTotalPercentageForInsurance($familyInsuranceId)
    {
        return static::where('family_insurance_id', $familyInsuranceId)
            ->sum('percentage');
    }

    /**
     * بررسی اینکه آیا مجموع درصدها معتبر است (حداکثر ۱۰۰٪)
     */
    public static function isValidTotalPercentage($familyInsuranceId, $excludeId = null)
    {
        $query = static::where('family_insurance_id', $familyInsuranceId);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->sum('percentage') <= 100;
    }
}
