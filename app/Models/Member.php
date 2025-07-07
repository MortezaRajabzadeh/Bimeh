<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Member extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    /**
     * Get the organization that introduced this member.
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'charity_id');
    }

    /**
     * فیلدهای قابل پر شدن
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'family_id',
        'charity_id',
        'first_name',
        'last_name',
        'national_code',
        'father_name',
        'birth_date',
        'gender',
        'marital_status',
        'education',
        'relationship',
        'relationship_fa',
        'is_head',
        'has_disability',
        'has_chronic_disease',
        'has_insurance',
        'insurance_type',
        'insurance_start_date',
        'insurance_end_date',
        'special_conditions',
        'problem_type',
        'occupation',
        'is_employed',
        'mobile',
        'phone',
        'sheba',
        'has_incomplete_data',
        'incomplete_data_details',
        'incomplete_data_updated_at',
    ];

    /**
     * فیلدهای تبدیل به مقادیر داده‌ای
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_head' => 'boolean',
        'has_disability' => 'boolean',
        'has_chronic_disease' => 'boolean',
        'has_insurance' => 'boolean',
        'is_employed' => 'boolean',
        'problem_type' => 'array',
        'has_incomplete_data' => 'boolean',
        'incomplete_data_details' => 'array',
        'incomplete_data_updated_at' => 'datetime',
    ];

    /**
     * تنظیمات مدیا لایبرری
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('special_disease_documents')
            ->singleFile()
            ->useDisk('public');

        $this->addMediaCollection('disability_documents')
            ->singleFile()
            ->useDisk('public');
    }

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
     * رابطه با خیریه
     */
    public function charity()
    {
        return $this->belongsTo(Organization::class, 'charity_id');
    }

    /**
     * نام کامل عضو
     */
    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * ترجمه وضعیت تأهل به فارسی
     */
    public function getMaritalStatusFaAttribute()
    {
        $status = [
            'single' => 'مجرد',
            'married' => 'متأهل',
            'divorced' => 'مطلقه',
            'widowed' => 'بیوه'
        ];

        return $status[$this->marital_status] ?? $this->marital_status;
    }

    /**
     * ترجمه جنسیت به فارسی
     */
    public function getGenderFaAttribute()
    {
        $genders = [
            'male' => 'مرد',
            'female' => 'زن'
        ];

        return $genders[$this->gender] ?? $this->gender;
    }

    /**
     * ترجمه نسبت به فارسی
     */
    // public function getRelationshipFaAttribute()
    // {
    //     $relationships = [
    //         'head' => 'سرپرست',
    //         'spouse' => 'همسر',
    //         'child' => $this->gender === 'male' ? 'پسر' : ($this->gender === 'female' ? 'دختر' : 'فرزند'),
    //         'parent' => $this->gender === 'male' ? 'پدر' : ($this->gender === 'female' ? 'مادر' : 'والدین'),
    //         'father' => 'پدر',
    //         'mother' => 'مادر',
    //         'brother' => 'برادر',
    //         'sister' => 'خواهر',
    //         'grandfather' => 'پدربزرگ',
    //         'grandmother' => 'مادربزرگ',
    //         'uncle' => 'عمو/دایی',
    //         'aunt' => 'عمه/خاله',
    //         'nephew' => 'برادرزاده/خواهرزاده',
    //         'niece' => 'برادرزاده/خواهرزاده',
    //         'cousin' => 'پسرعمو/دخترعمو/پسردایی/دختردایی',
    //         'son_in_law' => 'داماد',
    //         'daughter_in_law' => 'عروس',
    //         'other' => 'سایر',
    //     ];
    //     return $relationships[$this->relationship] ?? 'سایر';
    // }

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

    /**
     * کوئری برای اعضایی که بیمه‌شان منقضی شده
     */
    public function scopeExpiredInsurance($query)
    {
        return $query->whereNotNull('end_date')
            ->where('end_date', '<', now());
    }

    /**
     * فیلتر افراد دارای اطلاعات ناقص
     */
    public function scopeWithIncompleteData($query)
    {
        return $query->where('has_incomplete_data', true);
    }

    /**
     * بررسی اینکه آیا عضو دارای اطلاعات ناقص است
     */
    public function hasIncompleteData(): bool
    {
        return $this->has_incomplete_data;
    }

    /**
     * دریافت فهرست اطلاعات ناقص
     */
    public function getIncompleteDataList(): array
    {
        return $this->incomplete_data_details ?? [];
    }

    // در Observer یا متدهای مربوطه
    protected static function booted()
    {
        static::saved(function ($member) {
            $member->family->checkAndApplySingleParentCriteria();
        });
        
        static::deleted(function ($member) {
            $member->family->checkAndApplySingleParentCriteria();
        });
    }
}
