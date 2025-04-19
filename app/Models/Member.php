<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Member extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * فیلدهای قابل پر شدن
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'family_id',
        'first_name',
        'last_name',
        'national_code',
        'father_name',
        'birth_date',
        'gender',
        'marital_status',
        'education',
        'relationship',
        'is_head',
        'has_disability',
        'has_chronic_disease',
        'has_insurance',
        'insurance_type',
        'special_conditions',
        'occupation',
        'is_employed',
        'mobile',
        'phone',
    ];

    /**
     * فیلدهای تبدیل به مقادیر داده‌ای
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'is_head' => 'boolean',
        'has_disability' => 'boolean',
        'has_chronic_disease' => 'boolean',
        'has_insurance' => 'boolean',
        'is_employed' => 'boolean',
    ];

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'first_name', 'last_name', 'national_code', 'is_head',
                'has_disability', 'has_chronic_disease', 'has_insurance'
            ])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "عضو خانواده {$eventName} شد");
    }

    /**
     * رابطه با خانواده
     */
    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * نام کامل عضو
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * فیلتر افراد سرپرست خانوار
     */
    public function scopeHeads($query)
    {
        return $query->where('is_head', true);
    }

    /**
     * فیلتر افراد دارای معلولیت
     */
    public function scopeWithDisability($query)
    {
        return $query->where('has_disability', true);
    }

    /**
     * فیلتر افراد دارای بیماری مزمن
     */
    public function scopeWithChronicDisease($query)
    {
        return $query->where('has_chronic_disease', true);
    }

    /**
     * فیلتر افراد شاغل
     */
    public function scopeEmployed($query)
    {
        return $query->where('is_employed', true);
    }

    /**
     * فیلتر افراد دارای بیمه
     */
    public function scopeInsured($query)
    {
        return $query->where('has_insurance', true);
    }

    /**
     * فیلتر افراد بی‌بیمه
     */
    public function scopeUninsured($query)
    {
        return $query->where('has_insurance', false);
    }
} 