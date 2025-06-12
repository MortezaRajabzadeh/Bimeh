<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InsuranceAllocation extends Model
{
    use HasFactory, LogsActivity;
    
    protected $fillable = [
        'funding_transaction_id', 'family_id', 'amount', 'issue_date', 'paid_at', 'description'
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'issue_date', 'paid_at', 'description'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "تخصیص بیمه {$eventName} شد");
    }

    public function transaction()
    {
        return $this->belongsTo(FundingTransaction::class, 'funding_transaction_id');
    }

    public function family()
    {
        return $this->belongsTo(Family::class, 'family_id');
    }
} 
