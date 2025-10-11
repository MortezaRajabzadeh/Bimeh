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
        return $this->hasOneThrough(
            Family::class,
            FamilyInsurance::class,
            'id', // کلید در family_insurances
            'id', // کلید در families
            'family_insurance_id', // کلید در insurance_shares
            'family_id' // کلید در family_insurances
        );
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
     * ✅ رابطه با لاگ ایمپورت (بهبود یافته)
     */
    public function importLog()
    {
        return $this->belongsTo(ShareAllocationLog::class, 'import_log_id')
            ->select(['id', 'batch_id', 'description', 'total_amount', 'status', 'created_at']);
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
     * ✅ اسکوپ برای سهم‌های پرداخت شده
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * ✅ اسکوپ برای سهم‌های پرداخت نشده
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * ✅ اسکوپ برای فیلتر بر اساس خانواده
     */
    public function scopeForFamily($query, $familyId)
    {
        return $query->whereHas('familyInsurance', function($q) use ($familyId) {
            $q->where('family_id', $familyId);
        });
    }
    
    /**
     * اسکوپ برای سهم‌های manual (ایجاد شده به صورت دستی)
     * این سهم‌ها import_log_id ندارند
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeManual($query)
    {
        return $query->whereNull('import_log_id');
    }
    
    /**
     * اسکوپ برای سهم‌های bulk (ایجاد شده به صورت گروهی)
     * این سهم‌ها import_log_id دارند
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBulkAllocated($query)
    {
        return $query->whereNotNull('import_log_id');
    }
    
    /**
     * اسکوپ برای فیلتر بر اساس import_log_id
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $importLogId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFromImportLog($query, $importLogId)
    {
        return $query->where('import_log_id', $importLogId);
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
   /**
     * ✅ دریافت نام پرداخت کننده بر اساس نوع (اصلاح شده)
     * اولویت: نام ذخیره شده در payer_name > نام سازمان > نام کاربر
     */
    public function getPayerNameAttribute($value)
    {
        // اول چک کنیم که آیا مقدار مستقیم payer_name موجود است
        if (!empty($value)) {
            return $value;
        }
        
        // در غیر این صورت از سازمان یا کاربر استفاده کنیم
        if ($this->payer_organization_id && $this->payerOrganization) {
            return $this->payerOrganization->name;
        } elseif ($this->payer_user_id && $this->payerUser) {
            return $this->payerUser->name;
        }

        return 'نامشخص';
    }
        /**
     * ✅ محاسبه مجموع سهم‌های یک خانواده
     */
    public static function getTotalSharesForFamily($familyId)
    {
        return static::forFamily($familyId)->sum('amount');
    }

    /**
     * ✅ محاسبه مجموع درصد سهم‌های یک خانواده
     */
    public static function getTotalPercentageForFamily($familyId)
    {
        return static::forFamily($familyId)->sum('percentage');
    }

    /**
     * ✅ بررسی اینکه آیا سهم‌بندی کامل است (100%)
     */
    public static function isFullyAllocatedForFamily($familyId)
    {
        return static::getTotalPercentageForFamily($familyId) >= 100;
    }

    /**
     * دریافت نوع پرداخت کننده
     */
    public function getPayerTypeNameAttribute()
    {
        return $this->payerType?->name ?? '';
    }
    
    /**
     * چک کردن manual بودن سهم
     * 
     * @return bool
     */
    public function isManual(): bool
    {
        return is_null($this->import_log_id);
    }
    
    /**
     * چک کردن bulk بودن سهم
     * 
     * @return bool
     */
    public function isBulkAllocated(): bool
    {
        return !is_null($this->import_log_id);
    }
}
