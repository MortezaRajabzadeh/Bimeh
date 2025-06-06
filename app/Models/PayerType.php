<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PayerType extends Model
{
    use HasFactory, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'is_active'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "نوع پرداخت کننده {$eventName} شد");
    }

    /**
     * رابطه با سهم‌های بیمه
     */
    public function insuranceShares()
    {
        return $this->hasMany(InsuranceShare::class, 'payer_type_id');
    }

    /**
     * دریافت نوع‌های پرداخت کننده فعال
     */
    public static function getActiveTypes()
    {
        return self::where('is_active', true)->get();
    }

    /**
     * دریافت نوع پرداخت کننده با کد مشخص
     */
    public static function getByCode($code)
    {
        return self::where('code', $code)->first();
    }
}
