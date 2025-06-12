<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FundingTransaction extends Model
{
    use HasFactory, LogsActivity;
    
    protected $fillable = [
        'funding_source_id', 'amount', 'description', 'reference_no', 'allocated'
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['amount', 'description', 'reference_no', 'allocated'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "تراکنش بودجه {$eventName} شد");
    }

    public function source()
    {
        return $this->belongsTo(FundingSource::class, 'funding_source_id');
    }
} 
