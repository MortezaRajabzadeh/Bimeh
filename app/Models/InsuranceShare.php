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
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'family_insurance_id',
        'funding_source_id',
        'percentage',
        'amount',
        'description',
        'created_by',
        'import_log_id',
        'payer_type_id',
        'payer_name',
        'payer_organization_id',
        'payer_user_id',
        'is_paid',
        'payment_date',
        'payment_reference'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'is_paid' => 'boolean',
        'payment_date' => 'datetime',
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['percentage', 'amount', 'description'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "سهم‌بندی بیمه {$eventName} شد");
    }

    /**
     * رابطه با خانواده (از طریق بیمه خانواده)
     */
    public function family()
    {
        return $this->belongsTo(Family::class, 'id')->withDefault();
    }

    /**
     * رابطه با بیمه خانواده
     */
    public function familyInsurance()
    {
        return $this->belongsTo(FamilyInsurance::class, 'family_insurance_id');
    }

    /**
     * رابطه با منبع مالی
     */
    public function fundingSource()
    {
        return $this->belongsTo(FundingSource::class);
    }

    /**
     * رابطه با کاربر ایجاد کننده
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * رابطه با لاگ ایمپورت
     */
    public function importLog()
    {
    }

    /**
     * محاسبه مبلغ بر اساس درصد و مبلغ کل حق بیمه
     */
    public function calculateAmount($totalPremium)
    {
        $this->amount = ($this->percentage / 100) * $totalPremium;
        return $this->amount;
    }

    /**
     * دریافت مبلغ فرمت شده
     */
    public function getFormattedAmountAttribute()
    {
        return number_format($this->amount) . ' تومان';
    }

    /**
     * دریافت درصد فرمت شده
     */
    public function getFormattedPercentageAttribute()
    {
        return $this->percentage . '%';
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
     * رابطه با نوع پرداخت کننده
     */
    public function payerType()
    {
        return $this->belongsTo(PayerType::class, 'payer_type_id');
    }

    /**
     * دریافت نام پرداخت کننده بر اساس نوع
     */
    public function getPayerNameAttribute($value)
    {
        if ($this->payer_organization_id) {
            return $this->payerOrganization?->name ?? $value;
        } elseif ($this->payer_user_id) {
            return $this->payerUser?->name ?? $value;
        }
        
        return $value;
    }

    /**
     * دریافت نوع پرداخت کننده
     */
    public function getPayerTypeNameAttribute()
    {
        return $this->payerType?->name ?? '';
    }
}
