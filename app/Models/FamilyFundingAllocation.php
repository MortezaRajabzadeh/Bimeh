<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\Auth;

class FamilyFundingAllocation extends Model
{
    /** @use HasFactory<\Database\Factories\FamilyFundingAllocationFactory> */
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'family_id',
        'funding_source_id',
        'import_log_id',
        'transaction_id',
        'amount',
        'percentage',
        'description',
        'status',
        'approved_at',
        'approver_id',
        'paid_at',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'status_fa',
        'formatted_amount',
        'formatted_percentage'
    ];

    // وضعیت‌های مختلف تخصیص
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved'; 
    const STATUS_PAID = 'paid';

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['percentage', 'amount', 'status'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "تخصیص بودجه خانواده {$eventName} شد");
    }

    /**
     * رابطه با خانواده
     */
    public function family()
    {
        return $this->belongsTo(Family::class);
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
     * رابطه با کاربر تایید کننده
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * رابطه با لاگ آپلود فایل اکسل
     */
    public function importLog()
    {
    }

    /**
     * رابطه با تراکنش مالی
     */
    public function transaction()
    {
        return $this->belongsTo(FundingTransaction::class, 'transaction_id');
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
     * تایید تخصیص بودجه
     */
    public function approve($userId = null)
    {
        $this->status = self::STATUS_APPROVED;
        $this->approved_at = now();
        
        // استفاده از Auth facade برای احراز هویت
        $this->approved_by = $userId ?? (Auth::id() ?: null);
        
        $this->save();
        
        return $this;
    }

    /**
     * پرداخت شده علامت زدن
     */
    public function markAsPaid()
    {
        $this->status = self::STATUS_PAID;
        $this->save();
        
        return $this;
    }

    /**
     * فیلتر بر اساس وضعیت
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * فیلتر تخصیصات تایید شده
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * فیلتر تخصیصات پرداخت شده
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * فیلتر تخصیصات در انتظار
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * مجموع درصد تخصیصات برای یک خانواده
     */
    public static function getTotalPercentageForFamily($familyId)
    {
        return static::where('family_id', $familyId)
            ->sum('percentage');
    }

    /**
     * بررسی اینکه آیا مجموع درصدها معتبر است (حداکثر ۱۰۰٪)
     */
    public static function isValidTotalPercentage($familyId, $excludeId = null)
    {
        $query = static::where('family_id', $familyId);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->sum('percentage') <= 100;
    }

    /**
     * ترجمه وضعیت به فارسی
     */
    public function getStatusFaAttribute()
    {
        $statuses = [
            self::STATUS_PENDING => 'در انتظار',
            self::STATUS_APPROVED => 'تایید شده',
            self::STATUS_PAID => 'پرداخت شده',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * فرمت‌بندی مبلغ تخصیص با جداکننده فارسی
     */
    public function getFormattedAmountAttribute()
    {
        if (!$this->amount) {
            return '۰';
        }
        
        return self::formatNumber($this->amount) . ' تومان';
    }

    /**
     * فرمت‌بندی درصد تخصیص
     */
    public function getFormattedPercentageAttribute()
    {
        if (!$this->percentage) {
            return '۰٪';
        }
        
        return $this->percentage . '٪';
    }
    
    /**
     * تبدیل اعداد به فرمت فارسی با جداکننده فارسی
     */
    public static function formatNumber($number)
    {
        if (!$number) {
            return '۰';
        }
        
        // فرمت‌بندی با جداکننده فارسی
        $formatted = number_format($number, 0, '.', '٬');
        
        return $formatted;
    }

    /**
     * محاسبه مجموع بودجه تخصیص یافته برای یک خانواده
     * 
     * @param int $familyId شناسه خانواده
     * @return float مجموع بودجه تخصیص یافته
     */
    public static function getTotalAllocatedAmountForFamily($familyId)
    {
        return static::where('family_id', $familyId)
            ->where('status', '!=', self::STATUS_PENDING)
            ->sum('amount');
    }

    /**
     * بررسی وضعیت کلی تخصیص بودجه خانواده
     * 
     * @param int $familyId شناسه خانواده
     * @return array وضعیت تخصیص بودجه
     */
    public static function getAllocationStatus($familyId)
    {
        $totalAllocations = static::where('family_id', $familyId)->count();
        $approvedAllocations = static::where('family_id', $familyId)
            ->where('status', self::STATUS_APPROVED)
            ->count();
        $paidAllocations = static::where('family_id', $familyId)
            ->where('status', self::STATUS_PAID)
            ->count();
        
        $totalAmount = static::getTotalAllocatedAmountForFamily($familyId);
        $totalPercentage = static::getTotalPercentageForFamily($familyId);

        return [
            'total_allocations' => $totalAllocations,
            'approved_allocations' => $approvedAllocations,
            'paid_allocations' => $paidAllocations,
            'total_amount' => $totalAmount,
            'formatted_total_amount' => self::formatNumber($totalAmount) . ' تومان',
            'total_percentage' => $totalPercentage,
            'is_fully_allocated' => $totalPercentage == 100,
            'is_fully_paid' => $paidAllocations == $totalAllocations
        ];
    }
}
