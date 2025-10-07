<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Support\Facades\Dispatch;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Enums\InsuranceWizardStep;

class Family extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

    /**
     * The criteria that belong to the family.
     */
    public function criteria()
    {
        return $this->belongsToMany(RankSetting::class, 'family_criteria', 'family_id', 'rank_setting_id')
                    ->withPivot(['notes'])
                    ->withTimestamps();
    }

    /**
     * تعریف رابطه "یک به چند" با مدارک خانواده.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documents(): HasMany
    {
        // فرض بر این است که مدل مدارک شما Document نام دارد
        return $this->hasMany(Document::class);
    }

    /**
     * فیلدهای قابل پر شدن
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'family_code',
        'province_id',
        'city_id',
        'district_id',
        'region_id',
        'head_id',
        'charity_id',
        'insurance_id',
        'registered_by',
        'address',
        'postal_code',
        'housing_status',
        'housing_description',
        'status',
        'rejection_reason',
        'poverty_confirmed',
        'additional_info',
        'verified_at',
        'is_insured',
        'acceptance_criteria',
        'calculated_rank',
        'rank_calculated_at',
        'wizard_status',
        'last_step_at',
    ];

    /**
     * فیلدهای تبدیل به مقادیر داده‌ای
     *
     * @var array<string, string>
     */
    protected $casts = [
        'poverty_confirmed' => 'boolean',
        'verified_at' => 'datetime',

        'is_insured' => 'boolean',
        'rank_calculated_at' => 'datetime',
        'wizard_status' => 'string',
        'last_step_at' => 'array',
        'acceptance_criteria' => 'array',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($family) {
            $family->wizard_status = 'pending';
        });

        // محاسبه رتبه موقع ایجاد خانواده جدید
        static::created(function ($family) {
            // اگر acceptance_criteria دارد یا اعضایش معیار دارند
            if (!empty($family->acceptance_criteria) || $family->criteria()->exists()) {
                // محاسبه رتبه در background تا مانع performance نشود
                dispatch(function() use ($family) {
                    $family->calculateRank();
                })->afterResponse();
            }
        });

        // به‌روزرسانی خودکار رتبه‌بندی هنگام تغییر acceptance_criteria یا معیارها
        static::updated(function ($family) {
            if ($family->isDirty('acceptance_criteria') || $family->isDirty('criteria')) {
                dispatch(function() use ($family) {
                    $family->calculateRank();
                })->afterResponse();
            }
        });

        // به‌روزرسانی خودکار رتبه‌بندی هنگام تغییر معیارهای رتبه‌بندی
        static::saved(function ($family) {
            if ($family->wasChanged('acceptance_criteria')) {
                dispatch(function() use ($family) {
                    $family->calculateRank();
                })->afterResponse();
            }
        });

        // محاسبه مجدد رتبه موقع به‌روزرسانی acceptance_criteria
        static::updated(function ($family) {
            if ($family->isDirty('acceptance_criteria')) {
                // محاسبه رتبه در background
                dispatch(function() use ($family) {
                    $family->calculateRank();
                })->afterResponse();
            }
        });
    }

    /**
     * تنظیمات مدیا لایبرری
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('family_photos')
            ->singleFile()
            ->useDisk('public');
    }

    /**
     * تنظیمات لاگ فعالیت
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'poverty_confirmed', 'verified_at'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "خانواده {$eventName} شد");
    }

    /**
     * رابطه با سازمان معرف (خیریه)
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'charity_id');
    }

    /**
     * رابطه با خیریه (alias برای organization)
     */
    public function charity()
    {
        return $this->belongsTo(Organization::class, 'charity_id');
    }

    /**
     * رابطه با استان
     */
    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    /**
     * رابطه با شهر
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    /**
     * رابطه با دهستان
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * رابطه با سازمان بیمه
     */
    public function insuranceOrganization()
    {
        return $this->belongsTo(Organization::class, 'insurance_id');
    }

    /**
     * رابطه با کاربر ثبت‌کننده
     */
    public function registeredByUser()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
    public function membersWithInsurance()
    {
        return $this->hasMany(Member::class)->whereNotNull('insurance_type');
    }
    /**
     * رابطه با اعضای خانواده
     */
    public function members()
    {
        return $this->hasMany(\App\Models\Member::class);
    }

    /**
     * کوئری برای فیلتر کردن خانواده‌های در انتظار بررسی
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * کوئری برای فیلتر کردن خانواده‌های در حال بررسی
     */
    public function scopeReviewing($query)
    {
        return $query->where('status', 'reviewing');
    }

    /**
     * کوئری برای فیلتر کردن خانواده‌های تایید شده
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * کوئری برای فیلتر کردن خانواده‌های رد شده
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * دریافت سرپرست خانوار
     */
    public function head()
    {
        if ($this->head_id) {
            return $this->belongsTo(\App\Models\Member::class, 'head_id');
        }
        return $this->hasOne(\App\Models\Member::class)->where('is_head', true);
    }

    /**
     * تنظیم سرپرست خانوار
     */
    public function setHead($memberId)
    {
        // ابتدا تمام اعضا را غیر سرپرست کن
        $this->members()->update(['is_head' => false]);

        // عضو جدید را سرپرست کن
        $member = $this->members()->find($memberId);
        if ($member) {
            $member->update(['is_head' => true]);
            $this->update(['head_id' => $memberId]);

            // بررسی و اعمال معیار سرپرست خانوار زن
            $this->checkAndApplySingleParentCriteria();

            return true;
        }

        return false;
    }

    /**
     * بررسی اینکه آیا خانواده سرپرست دارد
     */
    public function hasHead()
    {
        return $this->head_id !== null || $this->members()->where('is_head', true)->exists();
    }

    /**
     * دریافت سرپرست خانوار (متد کمکی)
     */
    public function getHeadMember()
    {
        if ($this->head_id) {
            return $this->members()->find($this->head_id);
        }
        return $this->members()->where('is_head', true)->first();
    }

    public function region()
    {
        return $this->belongsTo(\App\Models\Region::class, 'region_id');
    }

    /**
     * کوئری برای خانواده‌هایی که بیمه‌شان منقضی شده و هنوز renewal نشده‌اند
     */
    public function scopeExpiredInsurance($query)
    {
        return $query->whereHas('insurances', function ($q) {
            $q->whereNotNull('end_date')
              ->where('end_date', '<', now());
        })->where('status', '!=', 'renewal');
    }

    public function insurances()
    {
        return $this->hasMany(FamilyInsurance::class);
    }

    /**
     * رابطه فقط با بیمه‌های نهایی شده (insured)
     */
    public function finalInsurances()
    {
        return $this->hasMany(FamilyInsurance::class)->where('status', 'insured');
    }

    /**
     * تعداد بیمه‌های این خانواده
     */
    public function insuranceCount()
    {
        return $this->insurances()->count();
    }

    /**
     * تعداد بیمه‌های نهایی شده این خانواده
     */
    public function finalInsuranceCount()
    {
        return $this->finalInsurances()->count();
    }

    /**
     * تعداد اعضای بیمه‌دار این خانواده
     */
    public function insuredMembersCount()
    {
        // اگر خانواده بیمه دارد، تعداد اعضا را برگردان
        if ($this->isInsured()) {
            return $this->members()->count();
        }

        return 0;
    }

    /**
     * آیا این خانواده بیمه دارد؟
     */
    public function isInsured()
    {
        // بررسی رابطه insurances یا فیلد is_insured
        return $this->insurances()->exists() ||
               $this->is_insured == true ||
               $this->is_insured == 1;
    }

    /**
     * آیا این خانواده بیمه نهایی شده دارد؟
     */
    public function hasFinalInsurance()
    {
        return $this->finalInsurances()->exists();
    }

    /**
     * لیست انواع بیمه‌های این خانواده
     */
    public function insuranceTypes()
    {
        // فقط از بیمه‌های نهایی شده استفاده می‌کنیم
        $types = $this->finalInsurances()->pluck('insurance_type')->unique();

        // اگر داده‌ای پیدا نشد، از insuranceShares با فیلتر استفاده کن
        if ($types->isEmpty()) {
            // از طریق رابطه insurance_shares به insurance_type دسترسی پیدا کن
            $insuranceIds = $this->finalInsurances()->pluck('id');
            $types = \App\Models\InsuranceShare::whereIn('family_insurance_id', $insuranceIds)
                ->join('family_insurances', 'insurance_shares.family_insurance_id', '=', 'family_insurances.id')
                ->where('family_insurances.status', 'insured')
                ->pluck('family_insurances.insurance_type')
                ->unique();
        }

        return $types;
    }

    /**
     * لیست پرداخت‌کنندگان حق بیمه‌های نهایی شده این خانواده
     */
    public function insurancePayers()
    {
        // فقط از بیمه‌های نهایی شده استفاده می‌کنیم
        $insuranceIds = $this->finalInsurances()->pluck('id')->toArray();

        if (empty($insuranceIds)) {
            return collect([]);
        }

        // استفاده از payer_type و payer_name از جدول insurance_shares
        $shares = \App\Models\InsuranceShare::whereIn('family_insurance_id', $insuranceIds)
            ->with(['payerType', 'payerOrganization', 'payerUser'])
            ->get();

        if ($shares->isEmpty()) {
            // اگر سهامی وجود نداشت، از insurance_payer استفاده کن (فقط از نهایی شده‌ها)
            return $this->finalInsurances()->pluck('insurance_payer')->unique();
        }

        // نمایش نام پرداخت‌کننده‌ها با استفاده از اکسسور getPayerNameAttribute
        $payerNames = $shares->map(function($share) {
            return $share->payer_name;
        })->filter()->unique()->values();

        return $payerNames;
    }

    /**
     * کوئری برای خانواده‌هایی که بیمه‌شان منقضی شده و هنوز renewal نشده‌اند
     */
    public function scopeRenewalCandidate($query)
    {
        return $query->whereHas('insurances', function ($q) {
            $q->whereNotNull('end_date')
              ->where('end_date', '<', now());
        })->where('status', '!=', 'renewal');
    }

    /**
     * رابطه با معیارهای رتبه‌بندی خانواده
     */
    public function familyCriteria()
    {
        return $this->hasMany(FamilyCriterion::class);
    }

    /**
     * دریافت معیارهای رتبه‌بندی خانواده به صورت رشته متنی
     */
    public function getRankCriteriaAttribute()
    {
        if ($this->attributes['rank_criteria']) {
            return $this->attributes['rank_criteria'];
        }

        $criteria = $this->familyCriteria()
            ->where('has_criteria', true)
            ->with('rankSetting')
            ->get()
            ->map(function ($criterion) {
                return $criterion->rankSetting->name;
            })
            ->unique()
            ->values()
            ->implode(', ');

        $this->rank_criteria = $criteria;
        $this->save();

        return $criteria;
    }



    /**
     * محاسبه امتیاز وزنی بر اساس معیارهای فعال
     *
     * @return float
     */
    public function calculateWeightedScore(): float
    {
        return $this->criteria() // This refers to family_criteria relationship which already has join with rank_settings
                        ->where('rank_settings.is_active', true) // Calculate score only for active rank settings
                        ->sum('rank_settings.weight');
    }

    /**
     * محاسبه مجدد رتبه‌بندی برای تمام خانواده‌ها
     *
     * @return void
     */
    public static function recalculateAllRanks()
    {

        $startTime = microtime(true);
        $count = 0;

        static::chunk(200, function ($families) use (&$count) {
            foreach ($families as $family) {
                $family->calculateRank();
                $count++;
            }
        });

        $executionTime = round(microtime(true) - $startTime, 2);

        return $count;
    }

    /**
     * محاسبه رتبه محرومیت خانواده بر اساس معیارها
     *
     * @return float
     */
    public function calculateRank()
    {
        // استفاده از کش برای بهبود عملکرد
        return Cache::remember('family_rank_' . $this->id, now()->addDay(), function () {
            $totalScore = 0;

            // محاسبه امتیاز بر اساس acceptance_criteria
            if (!empty($this->acceptance_criteria)) {
                $acceptanceCriteria = is_array($this->acceptance_criteria)
                    ? $this->acceptance_criteria
                    : json_decode($this->acceptance_criteria, true);

                if (is_array($acceptanceCriteria)) {
                    $totalScore += $this->calculateCriteriaScore($acceptanceCriteria);
                }
            }

            // محاسبه امتیاز بر اساس problem_type اعضا
            $membersProblemScore = $this->members->sum(function($member) {
                if (empty($member->problem_type)) return 0;

                $problemTypes = is_array($member->problem_type)
                    ? $member->problem_type
                    : json_decode($member->problem_type, true);

                return is_array($problemTypes) ? $this->calculateCriteriaScore($problemTypes) : 0;
            });

            $totalScore += $membersProblemScore;

            // محاسبه رتبه نهایی
            $maxPossibleScore = RankSetting::where('is_active', true)->sum('weight');

            $rank = $maxPossibleScore > 0
                ? round(($totalScore / $maxPossibleScore) * 100, 2)
                : 0;

            $this->update([
                'calculated_rank' => $rank,
                'rank_calculated_at' => now()
            ]);

            return $rank;
        });
    }

    /**
     * محاسبه امتیاز برای لیستی از معیارها
     *
     * @param array $criteria
     * @return float
     */
    protected function calculateCriteriaScore(array $criteria): float
    {
        return RankSetting::whereIn('name', $criteria)
            ->where('is_active', true)
            ->sum('weight');
    }
    /**
     * دریافت رتبه محرومیت (محاسبه شده یا محاسبه مجدد)
     */
    public function getRank($recalculate = false)
    {
        if ($recalculate || $this->calculated_rank === null || $this->rank_calculated_at === null) {
            $this->calculateRank();
        }
        return $this->calculated_rank;
    }

    /**
     * بررسی اینکه آیا خانواده معیار خاصی دارد
     */
    public function hasCriteria($criteriaKey)
    {
        return $this->familyCriteria()
            ->whereHas('rankSetting', function ($query) use ($criteriaKey) {
                $query->where('key', $criteriaKey)->where('is_active', true);
            })
            ->where('has_criteria', true)
            ->exists();
    }

    /**
     * اضافه کردن معیار به خانواده
     */
    public function addCriteria($rankSettingId, $notes = null)
    {
        // لاگ دقیقا زمان ثبت در جدول family_criteria
        // Log::info('Adding criteria to family', [
        //     'family_id' => $this->id,
        //     'rank_setting_id' => $rankSettingId,
        //     'notes' => $notes,
        //     'datetime' => now()->format('Y-m-d H:i:s'),
        //     'call_trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)
        // ]);

        return $this->familyCriteria()->updateOrCreate(
            ['rank_setting_id' => $rankSettingId],
            [
                'has_criteria' => true,
                'notes' => $notes,
            ]
        );
    }

    public function removeCriteria($rankSettingId)
    {
        return $this->familyCriteria()
            ->where('rank_setting_id', $rankSettingId)
            ->update(['has_criteria' => false]);
    }

    /**
     * فیلتر خانواده‌ها بر اساس بازه رتبه
     */
    public function scopeByRankRange($query, $minRank = null, $maxRank = null)
    {
        if ($minRank !== null) {
            $query->where('calculated_rank', '>=', $minRank);
        }

        if ($maxRank !== null) {
            $query->where('calculated_rank', '<=', $maxRank);
        }

        return $query;
    }

    /**
     * فیلتر خانواده‌ها بر اساس وجود معیار خاص
     */
    public function scopeWithCriteria($query, $criteriaKey)
    {
        return $query->whereHas('familyCriteria', function ($q) use ($criteriaKey) {
            $q->whereHas('rankSetting', function ($subQ) use ($criteriaKey) {
                $subQ->where('key', $criteriaKey)->where('is_active', true);
            })->where('has_criteria', true);
        });
    }

    /**
     * فیلتر خانواده‌ها بر اساس عدم وجود معیار خاص
     */
    public function scopeWithoutCriteria($query, $criteriaKey)
    {
        return $query->whereDoesntHave('familyCriteria', function ($q) use ($criteriaKey) {
            $q->whereHas('rankSetting', function ($subQ) use ($criteriaKey) {
                $subQ->where('key', $criteriaKey)->where('is_active', true);
            })->where('has_criteria', true);
        });
    }

    /**
     * تست فوری محاسبه رتبه (برای debug)
     */
    public function testRankCalculation()
    {
        $startTime = microtime(true);

        // محاسبه رتبه
        $rank = $this->calculateRank();

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // به میلی‌ثانیه

        return [
            'rank' => $rank,
            'execution_time_ms' => round($executionTime, 2)
        ];
    }

    /**
     * دریافت درصد تکمیل اطلاعات هویتی اعضای خانواده
     *
     * @return array
     */
    public function getIdentityValidationStatus(): array
{
    $baseRequiredFields = config('ui.family_validation_icons.identity.required_fields', [
        'first_name', 'last_name', 'national_code'
    ]);

    $members = $this->members;
    $totalMembers = $members->count();

    if ($totalMembers === 0) {
        return [
            'status' => 'none',
            'percentage' => 0,
            'message' => 'هیچ عضوی ثبت نشده است',
            'details' => []
        ];
    }

    $totalCompletionPercentage = 0;
    $memberDetails = [];
    $completeMembersCount = 0;

    foreach ($members as $member) {
        $requiredFields = $baseRequiredFields;
        $completedFields = 0;
        $memberFieldStatus = [];

        // اگر عضو بیماری خاص دارد، مدرک را به فیلدهای required اضافه کن
        $hasSpecialDisease = is_array($member->problem_type) && in_array('special_disease', $member->problem_type);
        if ($hasSpecialDisease) {
            $requiredFields[] = 'special_disease_document';
        }

        foreach ($requiredFields as $field) {
            if ($field === 'special_disease_document') {
                $isComplete = $member->getMedia('special_disease_documents')->count() > 0;
            } else {
                $isComplete = !empty($member->{$field});
            }

            if ($isComplete) {
                $completedFields++;
            }
            $memberFieldStatus[$field] = $isComplete;
        }

        // محاسبه درصد تکمیل برای هر عضو
        $memberCompletionRate = (count($requiredFields) > 0)
            ? ($completedFields / count($requiredFields)) * 100
            : 100;

        // اضافه کردن درصد تکمیل این عضو به مجموع کل
        $totalCompletionPercentage += $memberCompletionRate;

        // شمارش اعضایی که 100% کامل هستند
        if ($memberCompletionRate == 100) {
            $completeMembersCount++;
        }

        $memberDetails[] = [
            'member_id' => $member->id,
            'name' => $member->first_name . ' ' . $member->last_name,
            'completion_rate' => round($memberCompletionRate),
            'field_status' => $memberFieldStatus,
            'is_head' => $member->is_head,
            'has_special_disease' => $hasSpecialDisease
        ];
    }

    // محاسبه درصد میانگین کل
    $averagePercentage = round($totalCompletionPercentage / $totalMembers);

    // تعیین وضعیت کلی
    if ($averagePercentage == 100) {
        $status = 'complete';
        $message = "اطلاعات هویتی همه {$totalMembers} عضو کامل است";
    } elseif ($averagePercentage > 0) {
        $status = 'warning';
        $message = "{$completeMembersCount} از {$totalMembers} عضو اطلاعات کامل دارند";
    } else {
        $status = 'incomplete';
        $message = 'هیچ عضوی اطلاعات کامل ندارد';
    }

    return [
        'status' => $status,
        'percentage' => $averagePercentage,
        'message' => $message,
        'complete_members' => $completeMembersCount,
        'total_members' => $totalMembers,
        'details' => $memberDetails
    ];
}

    /**
     * بررسی وضعیت محرومیت منطقه‌ای خانواده
     *
     * @return array
     */
    public function getLocationValidationStatus(): array
    {
        // متغیرهای اصلی
        $province = null;
        $city = null;
        $district = null;
        $isDeprived = false;
        $deprivationDetails = [];
        $path = [];

        // بررسی دهستان (دقیق‌ترین سطح)
        if ($this->district_id && $this->district) {
            $district = $this->district;
            $path[] = 'دهستان: ' . $district->name;
            
            // بررسی محرومیت دهستان
            if ($district->is_deprived) {
                $isDeprived = true;
                $deprivationDetails[] = 'دهستان محروم';
            }
            
            // دریافت شهرستان از طریق دهستان
            if ($district->city) {
                $city = $district->city;
            }
        }

        // بررسی شهرستان
        if (!$city && $this->city_id && $this->city) {
            $city = $this->city;
        }
        
        if ($city) {
            $path[] = 'شهرستان: ' . $city->name;
            
            // بررسی محرومیت شهرستان
            if ($city->is_deprived) {
                $isDeprived = true;
                $deprivationDetails[] = 'شهرستان محروم';
            }
            
            // دریافت استان از طریق شهرستان
            if ($city->province) {
                $province = $city->province;
            }
        }

        // بررسی استان
        if (!$province && $this->province_id && $this->province) {
            $province = $this->province;
        }
        
        // اگر از طریق خیریه باشد
        if (!$province && !$city && !$district) {
            if ($this->organization && $this->organization->district) {
                $orgDistrict = $this->organization->district;
                $district = $orgDistrict;
                $path[] = 'دهستان خیریه: ' . $orgDistrict->name;
                
                if ($orgDistrict->is_deprived) {
                    $isDeprived = true;
                    $deprivationDetails[] = 'دهستان خیریه محروم';
                }
                
                if ($orgDistrict->city) {
                    $city = $orgDistrict->city;
                    $path[] = 'شهرستان خیریه: ' . $city->name;
                    
                    if ($city->is_deprived) {
                        $isDeprived = true;
                        $deprivationDetails[] = 'شهرستان خیریه محروم';
                    }
                    
                    if ($city->province) {
                        $province = $city->province;
                    }
                }
            }
        }
        
        if ($province) {
            $path[] = 'استان: ' . $province->name;
            
            // بررسی رتبه محرومیت استان (رتبه‌های پایین‌تر = محروم‌تر)
            $deprivationRank = $province->deprivation_rank ?? null;
            if ($deprivationRank && $deprivationRank <= 10) { // فرض: استان‌های با رتبه 1-10 محروم هستند
                $isDeprived = true;
                $deprivationDetails[] = sprintf('استان محروم (رتبه %d)', $deprivationRank);
            }
        }

        // اگر هیچ اطلاعات جغرافیایی نداریم
        if (!$province && !$city && !$district) {
            return [
                'status' => 'unknown',
                'message' => 'اطلاعات منطقه جغرافیایی نامشخص است',
                'province_name' => null,
                'city_name' => null,
                'district_name' => null,
                'is_deprived' => null,
                'deprivation_details' => [],
                'path' => []
            ];
        }

        // تعیین وضعیت و پیام
        if ($isDeprived) {
            $status = 'complete'; // سبز - منطقه محروم (مطلوب برای حمایت)
            $message = 'منطقه محروم: ' . implode('، ', $deprivationDetails);
        } else {
            $status = 'incomplete'; // قرمز - منطقه غیرمحروم
            $message = 'منطقه غیرمحروم - ';
            
            $locationNames = [];
            if ($district) $locationNames[] = 'دهستان: ' . $district->name;
            if ($city) $locationNames[] = 'شهرستان: ' . $city->name;
            if ($province) $locationNames[] = 'استان: ' . $province->name;
            
            $message .= implode('، ', $locationNames);
        }

        return [
            'status' => $status,
            'message' => $message,
            'province_name' => $province ? $province->name : null,
            'city_name' => $city ? $city->name : null,
            'district_name' => $district ? $district->name : null,
            'is_deprived' => $isDeprived,
            'deprivation_rank' => $deprivationRank ?? null,
            'deprivation_details' => $deprivationDetails,
            'path' => $path
        ];
    }

    /**
     * بررسی وضعیت آپلود مدارک مورد نیاز
     *
     * @return array
     */
    public function getDocumentsValidationStatus(): array
    {
        $documentTypes = config('ui.family_validation_icons.documents.document_types', [
            'special_disease' => 'مدرک بیماری خاص',
            'disability' => 'مدرک معلولیت',
            'chronic_disease' => 'مدرک بیماری مزمن'
        ]);

        $members = $this->members;
        $membersRequiringDocs = collect();
        $membersWithCompleteDocs = 0;
        $memberDetails = [];

        foreach ($members as $member) {
            $requiredDocTypes = [];
            $memberDocStatus = [];

            // بررسی اینکه عضو نیاز به چه مدارکی دارد
            if ($member->has_chronic_disease) {
                $requiredDocTypes[] = 'chronic_disease';
            }
            if ($member->has_disability) {
                $requiredDocTypes[] = 'disability';
            }
            // فرض می‌کنیم فیلد special_disease وجود دارد یا از chronic_disease استفاده می‌کنیم
            if ($member->has_chronic_disease) { // یا has_special_disease اگر فیلد جداگانه‌ای دارید
                $requiredDocTypes[] = 'special_disease';
            }

            if (empty($requiredDocTypes)) {
                continue; // این عضو نیاز به مدرک ندارد
            }

            $membersRequiringDocs->push($member);

            // بررسی مدارک آپلود شده (از media library استفاده می‌کنیم)
            $uploadedDocs = 0;

            foreach ($requiredDocTypes as $docType) {
                // بررسی وجود مدرک در media collection
                $hasDocument = $member->hasMedia($docType) ||
                              $this->hasMedia("member_{$member->id}_{$docType}");

                if ($hasDocument) {
                    $uploadedDocs++;
                }

                $memberDocStatus[$docType] = [
                    'required' => true,
                    'uploaded' => $hasDocument,
                    'label' => $documentTypes[$docType] ?? $docType
                ];
            }

            $memberCompletionRate = count($requiredDocTypes) > 0 ?
                ($uploadedDocs / count($requiredDocTypes)) * 100 : 100;

            if ($memberCompletionRate === 100) {
                $membersWithCompleteDocs++;
            }

            $memberDetails[] = [
                'member_id' => $member->id,
                'name' => $member->first_name . ' ' . $member->last_name,
                'required_docs' => $requiredDocTypes,
                'uploaded_docs' => $uploadedDocs,
                'completion_rate' => $memberCompletionRate,
                'doc_status' => $memberDocStatus,
                'is_head' => $member->is_head
            ];
        }

        $totalRequiringDocs = $membersRequiringDocs->count();

        if ($totalRequiringDocs === 0) {
            return [
                'status' => 'complete',
                'percentage' => 100,
                'message' => 'هیچ عضوی نیاز به مدرک خاصی ندارد',
                'members_requiring_docs' => 0,
                'members_with_complete_docs' => 0,
                'details' => []
            ];
        }

        $overallPercentage = ($membersWithCompleteDocs / $totalRequiringDocs) * 100;

        // تعیین وضعیت بر اساس درصد تکمیل
        $thresholds = config('ui.validation_thresholds');
        if ($overallPercentage >= $thresholds['complete_min']) {
            $status = 'complete';
            $message = 'مدارک تمام اعضای نیازمند کامل است';
        } elseif ($overallPercentage >= $thresholds['partial_min']) {
            $status = 'partial';
            $message = sprintf('مدارک %d از %d عضو نیازمند کامل است (%d%%)',
                $membersWithCompleteDocs, $totalRequiringDocs, round($overallPercentage));
        } else {
            $status = 'none';
            $message = 'مدارک اکثر اعضای نیازمند ناقص یا موجود نیست';
        }

        return [
            'status' => $status,
            'percentage' => round($overallPercentage),
            'message' => $message,
            'members_requiring_docs' => $totalRequiringDocs,
            'members_with_complete_docs' => $membersWithCompleteDocs,
            'details' => $memberDetails
        ];
    }

    /**
     * دریافت تمام وضعیت‌های اعتبارسنجی خانواده
     *
     * @return array
     */
    public function getAllValidationStatuses(): array
    {
        return [
            'identity' => $this->getIdentityValidationStatus(),
            'location' => $this->getLocationValidationStatus(),
            'documents' => $this->getDocumentsValidationStatus()
        ];
    }

    /**
     * محاسبه مجموع حق بیمه برای این خانواده
     * از طریق جدول family_insurances
     */
    public function getTotalPremiumAttribute()
    {
        // کش کردن مقدار برای بهبود عملکرد
        return Cache::remember('family_premium_' . $this->id, now()->addMinutes(60), function () {
            return \App\Models\FamilyInsurance::where('family_id', $this->id)
                ->sum('premium_amount') ?? 0;
        });
    }

    /**
     * محاسبه مجموع حق بیمه پرداخت شده
     */
    public function getTotalPaidPremiumAttribute()
    {
        return Cache::remember('family_paid_premium_' . $this->id, now()->addMinutes(60), function () {
            return $this->insurances()
                ->whereHas('shares', function($query) {
                    $query->where('is_paid', true);
                })
                ->sum('premium_amount') ?? 0;
        });
    }

    /**
     * رابطه با سهم‌های بیمه (از طریق FamilyInsurance)
     */
    public function insuranceShares()
    {
        return $this->hasManyThrough(
            \App\Models\InsuranceShare::class,
            \App\Models\FamilyInsurance::class,
            'family_id', // کلید خارجی در جدول واسط (family_insurances)
            'family_insurance_id', // کلید خارجی در جدول هدف (insurance_shares)
            'id', // کلید اصلی در این مدل (families)
            'id' // کلید اصلی در جدول واسط (family_insurances)
        );
    }

    /**
     * دریافت wizard_status به صورت enum اگر مقدار موجود باشد
     *
     * @param mixed $value
     * @return InsuranceWizardStep|null
     */
    public function getWizardStatusAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        try {
            return InsuranceWizardStep::from($value);
        } catch (\ValueError $e) {
            return null;
        }
    }

    /**
     * بررسی اینکه آیا مرحله wizard تکمیل شده است
     *
     * @param InsuranceWizardStep $step
     * @return bool
     */
    public function isStepCompleted(InsuranceWizardStep $step): bool
    {
        if (!$this->last_step_at) {
            return false;
        }

        $lastStepAt = is_array($this->last_step_at) ? $this->last_step_at : json_decode($this->last_step_at, true);
        return isset($lastStepAt[$step->value]);
    }

    /**
     * تکمیل یک مرحله از wizard
     *
     * @param InsuranceWizardStep $step
     * @param string|null $comment
     * @param array $extraData
     * @return void
     */
    public function completeStep(InsuranceWizardStep $step, ?string $comment = null, array $extraData = []): void
    {
        $lastStepAt = $this->last_step_at ?? [];
        if (is_string($lastStepAt)) {
            $lastStepAt = json_decode($lastStepAt, true) ?? [];
        }

        $lastStepAt[$step->value] = now()->toDateTimeString();
        $this->last_step_at = $lastStepAt;

        // اگر wizard_status تنظیم نشده باشد، آن را تنظیم می‌کنیم
        if (!$this->wizard_status) {
            $this->wizard_status = $step->value;
        }

        $this->save();

        // ثبت لاگ تکمیل مرحله
        $fromStatus = $this->wizard_status && $this->wizard_status !== $step->value ?
            InsuranceWizardStep::from($this->wizard_status) : null;

        // Log::info('Wizard step completed', [
        //     'family' => $this,
        //     'from_status' => $fromStatus ?? $step,
        //     'to_status' => $step,
        //     'comment' => $comment ?? "مرحله {$step->label()} تکمیل شد",
        //     'extra_data' => $extraData
        // ]);
    }

    /**
     * انتقال به مرحله بعدی wizard
     *
     * @param string|null $comment
     * @param array $extraData
     * @return InsuranceWizardStep|null
     */
    public function moveToNextStep(?string $comment = null, array $extraData = []): ?InsuranceWizardStep
    {
        if (!$this->wizard_status) {
            $initialStep = InsuranceWizardStep::PENDING;
            $this->wizard_status = $initialStep->value;
            $this->completeStep($initialStep, $comment, $extraData);
            $this->save();
            return $initialStep;
        }

        $currentStep = InsuranceWizardStep::from($this->wizard_status);
        $nextStep = $currentStep->nextStep();

        if ($nextStep) {
            // تکمیل مرحله فعلی اگر هنوز تکمیل نشده باشد
            if (!$this->isStepCompleted($currentStep)) {
                $this->completeStep($currentStep, "مرحله {$currentStep->label()} تکمیل شد", $extraData);
            }

            // تنظیم مرحله بعدی
            $this->wizard_status = $nextStep->value;
            $this->save();

            // ثبت لاگ انتقال به مرحله بعدی
            // Log::info('Wizard step moved to next', [
            //     'family' => $this,
            //     'from_status' => $currentStep,
            //     'to_status' => $nextStep,
            //     'comment' => $comment ?? "انتقال به مرحله {$nextStep->label()}",
            //     'extra_data' => $extraData
            // ]);

            return $nextStep;
        }

        return null;
    }

    /**
     * انتقال به مرحله قبلی wizard
     *
     * @param string|null $comment
     * @param array $extraData
     * @return InsuranceWizardStep|null
     */
    public function moveToPreviousStep(?string $comment = null, array $extraData = []): ?InsuranceWizardStep
    {
        if (!$this->wizard_status) {
            return null;
        }

        $currentStep = InsuranceWizardStep::from($this->wizard_status);
        $prevStep = $currentStep->previousStep();

        if ($prevStep) {
            // تنظیم مرحله قبلی
            $this->wizard_status = $prevStep->value;
            $this->save();

            // ثبت لاگ برگشت به مرحله قبلی
            // Log::info('Wizard step moved to previous', [
            //     'family' => $this,
            //     'from_status' => $currentStep,
            //     'to_status' => $prevStep,
            //     'comment' => $comment ?? "بازگشت به مرحله {$prevStep->label()}",
            //     'extra_data' => $extraData
            // ]);

            return $prevStep;
        }

        return null;
    }

    /**
     * همگام‌سازی وضعیت قدیمی با wizard جدید
     *
     * @return InsuranceWizardStep
     */
    public function syncWizardStatus()
    {
        $oldStatus = $this->status;

        // تبدیل وضعیت قدیمی به wizard جدید
        $wizardStatus = match($oldStatus) {
            'pending' => InsuranceWizardStep::PENDING,
            'reviewing' => InsuranceWizardStep::REVIEWING,
            'approved' => InsuranceWizardStep::APPROVED,
            'insured' => InsuranceWizardStep::INSURED,
            'renewal' => InsuranceWizardStep::RENEWAL,
            default => InsuranceWizardStep::PENDING
        };

        // بررسی وضعیت بیمه شدن
        if ($oldStatus === 'approved' && $this->is_insured) {
            $wizardStatus = InsuranceWizardStep::INSURED;
        }

        // ذخیره وضعیت wizard
        $this->wizard_status = $wizardStatus->value;
        $this->save();

        return $wizardStatus;
    }

    /**
     * همگام‌سازی معیارهای پذیرش خانواده بر اساس problem_type اعضا
     */
    public function syncAcceptanceCriteriaFromMembers()
    {
        // جمع‌آوری تمام معیارهای فردی اعضا (به فارسی)
        $allMemberCriteria = [];
        
        foreach ($this->members as $member) {
            // دریافت معیارها به فارسی برای نمایش یکدست
            $memberProblemTypes = $member->getProblemTypesArray(true); // true = Persian format
            foreach ($memberProblemTypes as $problemType) {
                if (!in_array($problemType, $allMemberCriteria)) {
                    $allMemberCriteria[] = $problemType;
                }
            }
        }
        
        // دریافت معیارهای فعلی خانواده
        $currentCriteria = $this->acceptance_criteria ?? [];
        if (is_string($currentCriteria)) {
            $currentCriteria = json_decode($currentCriteria, true) ?? [];
        }
        
        // لیست معیارهای فردی معتبر (از ProblemTypeHelper)
        $validProblemTypes = \App\Helpers\ProblemTypeHelper::getPersianValues();
        
        // حفظ معیارهای دستی (آنهایی که معیار فردی نیستند - مثل "سرپرست خانوار زن")
        $manualCriteria = array_filter($currentCriteria, function($criteria) use ($validProblemTypes) {
            return !in_array($criteria, $validProblemTypes);
        });
        
        // ترکیب معیارهای دستی با معیارهای اعضا
        $newCriteria = array_merge($manualCriteria, $allMemberCriteria);
        
        // حذف تکراری و مرتب‌سازی
        $newCriteria = array_unique($newCriteria);
        sort($newCriteria);
        
        // به‌روزرسانی فقط در صورت تغییر
        if (array_diff($currentCriteria, $newCriteria) || array_diff($newCriteria, $currentCriteria)) {
            $this->acceptance_criteria = array_values($newCriteria);
            $this->save();
            
            \Log::info('Family acceptance_criteria synced from members', [
                'family_id' => $this->id,
                'family_code' => $this->family_code,
                'old_criteria' => $currentCriteria,
                'new_criteria' => $newCriteria,
                'member_criteria' => $allMemberCriteria,
                'manual_criteria' => $manualCriteria,
                'valid_problem_types' => $validProblemTypes
            ]);
            
            // محاسبه مجدد رتبه
            $this->calculateRank();
        }
        
        return $this;
    }

    /**
     * بررسی و اعمال معیار سرپرست خانوار زن
     */
    public function checkAndApplySingleParentCriteria()
    {
        // پیدا کردن سرپرست خانواده
        $head = $this->members()->where('is_head', true)->first();

        if ($head) {
            // بررسی اینکه آیا سرپرست زن است
            $isFemaleHead = ($head->relationship_fa === 'مادر' || $head->gender === 'female');

            // بررسی اینکه آیا فقط یک سرپرست وجود دارد
            $parentsCount = $this->members()
                ->whereIn('relationship_fa', ['پدر', 'مادر'])
                ->count();

            $isSingleParent = ($parentsCount === 1);

            // اگر سرپرست زن و تک‌سرپرست باشد
            if ($isFemaleHead && $isSingleParent) {
                $acceptanceCriteria = $this->acceptance_criteria ?? [];

                if (!in_array('سرپرست خانوار زن', $acceptanceCriteria)) {
                    $acceptanceCriteria[] = 'سرپرست خانوار زن';
                    $this->acceptance_criteria = $acceptanceCriteria;
                    $this->save();

                    // محاسبه مجدد رتبه
                    $this->calculateRank();
                }
            } else {
                // حذف معیار اگر شرایط برقرار نباشد
                $acceptanceCriteria = $this->acceptance_criteria ?? [];
                $key = array_search('سرپرست خانوار زن', $acceptanceCriteria);

                if ($key !== false) {
                    unset($acceptanceCriteria[$key]);
                    $this->acceptance_criteria = array_values($acceptanceCriteria);
                    $this->save();

                    // محاسبه مجدد رتبه
                    $this->calculateRank();
                }
            }
        }
    }


}
