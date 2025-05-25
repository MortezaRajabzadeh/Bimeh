<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InsuranceImportLog extends Model
{
    use LogsActivity;
    
    protected $fillable = [
        'family_id',
        'row_data',
        'status',
        'message',
        'user_id',
        'file_name',
        'total_rows',
        'created_count',
        'updated_count',
        'skipped_count',
        'error_count',
        'total_insurance_amount',
        'family_codes',
        'updated_family_codes',
        'created_family_codes',
        'errors',
    ];

    protected $casts = [
        'row_data' => 'array',
        'family_codes' => 'array',
        'updated_family_codes' => 'array',
        'created_family_codes' => 'array',
        'errors' => 'string',
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_insurance_amount', 'created_count', 'updated_count', 'error_count'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "ایمپورت بیمه {$eventName} شد");
    }

    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 