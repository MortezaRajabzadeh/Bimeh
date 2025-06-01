<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FundingSource extends Model
{
    use HasFactory, LogsActivity;
    
    protected $fillable = [
        'type', 'name', 'description', 'is_active', 'annual_budget', 'budget'
    ];
    
    protected $casts = [
        'is_active' => 'boolean',
        'annual_budget' => 'decimal:2',
        'budget' => 'decimal:2'
    ];
    
    /**
     * رابطه با تراکنش‌های مالی
     */
    public function transactions()
    {
        return $this->hasMany(FundingTransaction::class);
    }
    
    /**
     * رابطه با تخصیص‌های بودجه
     */
    public function allocations()
    {
        return $this->hasMany(FamilyFundingAllocation::class);
    }

    /**
     * فیلتر برای منابع مالی فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
    
    /**
     * محاسبه بودجه باقیمانده
     */
    public function getRemainingBudgetAttribute()
    {
        $budget = $this->annual_budget ?? $this->budget ?? 0;
        $allocated = FamilyFundingAllocation::where('funding_source_id', $this->id)
            ->where('status', '!=', FamilyFundingAllocation::STATUS_PENDING)
            ->sum('amount');
            
        return max(0, $budget - $allocated);
    }
    
    /**
     * فرمت‌بندی بودجه با جداکننده فارسی
     */
    public function getFormattedBudgetAttribute()
    {
        $budget = $this->annual_budget ?? $this->budget ?? 0;
        return number_format($budget, 0, '.', '٬') . ' تومان';
    }
    
    /**
     * فرمت‌بندی بودجه باقیمانده با جداکننده فارسی
     */

    public function getFormattedRemainingBudgetAttribute()
    {
        return number_format($this->remaining_budget, 0, '.', '٬') . ' تومان';
    }

    /**
     * تنظیمات لاگ فعالیت
     */
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'description', 'is_active', 'annual_budget', 'budget'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "منبع بودجه {$eventName} شد");
    }
} 