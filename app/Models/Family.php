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

class Family extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, LogsActivity, InteractsWithMedia;

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
    ];

    /**
     * فیلدهای تبدیل به مقادیر داده‌ای
     *
     * @var array<string, string>
     */
    protected $casts = [
        'poverty_confirmed' => 'boolean',
        'verified_at' => 'datetime',
        'acceptance_criteria' => 'array',
        'is_insured' => 'boolean',
        'rank_calculated_at' => 'datetime',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot()
    {
        parent::boot();

        // محاسبه رتبه موقع ایجاد خانواده جدید
        static::created(function ($family) {
            // اگر acceptance_criteria دارد یا اعضایش معیار دارند
            if (($family->acceptance_criteria && is_array($family->acceptance_criteria) && count($family->acceptance_criteria) > 0) ||
                $family->familyCriteria()->where('has_criteria', true)->exists()) {
                
                // محاسبه رتبه در background تا مانع performance نشود
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
    public function insurance()
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
     * تعداد بیمه‌های این خانواده
     */
    public function insuranceCount()
    {
        return $this->insurances()->count();
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
     * لیست انواع بیمه‌های این خانواده
     */
    public function insuranceTypes()
    {
        return $this->insurances()->pluck('insurance_type')->unique();
    }

    /**
     * لیست پرداخت‌کنندگان حق بیمه این خانواده
     */
    public function insurancePayers()
    {
        return $this->insurances()->pluck('insurance_payer')->unique();
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
     * محاسبه رتبه محرومیت خانواده بر اساس معیارهای تعریف شده
     */
    public function calculateRank()
    {
        $totalWeight = 0;
        
        // 1. دریافت معیارهای فعال از جدول family_criteria
        $activeCriteria = $this->familyCriteria()
            ->with('rankSetting')
            ->where('has_criteria', true)
            ->get();
        
        foreach ($activeCriteria as $criterion) {
            if ($criterion->rankSetting && $criterion->rankSetting->is_active) {
                $totalWeight += $criterion->rankSetting->weight;
            }
        }
        
        // 2. اگر فیلد acceptance_criteria وجود دارد، از آن هم امتیاز بگیر
        if ($this->acceptance_criteria && is_array($this->acceptance_criteria) && count($this->acceptance_criteria) > 0) {
            // Cache کردن RankSetting ها برای بهبود عملکرد
            $rankSettings = Cache::remember('rank_settings_active', 60, function() {
                return \App\Models\RankSetting::where('is_active', true)
                    ->get()
                    ->keyBy('name');
            });
            
            foreach ($this->acceptance_criteria as $criteriaName) {
                if ($rankSettings->has($criteriaName)) {
                    $totalWeight += $rankSettings[$criteriaName]->weight;
                }
            }
        }
        
        // 3. به‌روزرسانی رتبه محاسبه شده (فقط اگر تغییر کرده باشد)
        if ($this->calculated_rank !== $totalWeight) {
            $this->updateQuietly([
                'calculated_rank' => $totalWeight,
                'rank_calculated_at' => now(),
            ]);
        }
        
        return $totalWeight;
    }

    /**
     * دریافت رتبه محرومیت (محاسبه شده یا محاسبه مجدد)
     */
    public function getRank($recalculate = false)
    {
        if ($recalculate || $this->calculated_rank === null || $this->rank_calculated_at === null) {
            return $this->calculateRank();
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
        return $this->familyCriteria()->updateOrCreate(
            ['rank_setting_id' => $rankSettingId],
            [
                'has_criteria' => true,
                'notes' => $notes,
            ]
        );
    }

    /**
     * حذف معیار از خانواده
     */
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
        return [
            'family_id' => $this->id,
            'acceptance_criteria' => $this->acceptance_criteria,
            'family_criteria_count' => $this->familyCriteria()->where('has_criteria', true)->count(),
            'current_calculated_rank' => $this->calculated_rank,
            'new_calculated_rank' => $this->calculateRank(),
            'rank_calculated_at' => $this->rank_calculated_at,
        ];
    }

    /**
     * محاسبه درصد تکمیل اطلاعات هویتی اعضای خانواده
     * 
     * @return array
     */
    public function getIdentityValidationStatus(): array
    {
        $requiredFields = config('ui.family_validation_icons.identity.required_fields', [
            'first_name', 'last_name', 'national_code', 'birth_date'
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
        
        $completeMembers = 0;
        $memberDetails = [];
        
        foreach ($members as $member) {
            $completedFields = 0;
            $memberFieldStatus = [];
            
            foreach ($requiredFields as $field) {
                $fieldValue = $member->{$field};
                $isComplete = !empty($fieldValue) && !is_null($fieldValue);
                
                if ($isComplete) {
                    $completedFields++;
                }
                
                $memberFieldStatus[$field] = $isComplete;
            }
            
            $memberCompletionRate = ($completedFields / count($requiredFields)) * 100;
            
            if ($memberCompletionRate === 100) {
                $completeMembers++;
            }
            
            $memberDetails[] = [
                'member_id' => $member->id,
                'name' => $member->first_name . ' ' . $member->last_name,
                'completion_rate' => $memberCompletionRate,
                'field_status' => $memberFieldStatus,
                'is_head' => $member->is_head
            ];
        }
        
        $overallPercentage = ($completeMembers / $totalMembers) * 100;
        
        // تعیین وضعیت بر اساس درصد تکمیل
        $thresholds = config('ui.validation_thresholds');
        if ($overallPercentage >= $thresholds['complete_min']) {
            $status = 'complete';
            $message = 'اطلاعات همه اعضا کامل است';
        } elseif ($overallPercentage >= $thresholds['partial_min']) {
            $status = 'partial';
            $message = sprintf('اطلاعات %d از %d عضو کامل است (%d%%)', 
                $completeMembers, $totalMembers, round($overallPercentage));
        } else {
            $status = 'none';
            $message = 'اطلاعات اکثر اعضا ناقص است';
        }
        
        return [
            'status' => $status,
            'percentage' => round($overallPercentage),
            'message' => $message,
            'complete_members' => $completeMembers,
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
        // بررسی اتصال از طریق charity → district → province
        $province = null;
        $isDeprived = null;
        $path = [];
        
        // مسیر اول: مستقیماً از خانواده
        if ($this->province_id && $this->province) {
            $province = $this->province;
            $path[] = 'خانواده → استان';
        }
        // مسیر دوم: از طریق شهر
        elseif ($this->city_id && $this->city && $this->city->province) {
            $province = $this->city->province;
            $path[] = 'خانواده → شهر → استان';
        }
        // مسیر سوم: از طریق منطقه
        elseif ($this->district_id && $this->district && $this->district->province) {
            $province = $this->district->province;
            $path[] = 'خانواده → منطقه → استان';
        }
        // مسیر چهارم: از طریق خیریه
        elseif ($this->organization && $this->organization->district && $this->organization->district->province) {
            $province = $this->organization->district->province;
            $path[] = 'خانواده → خیریه → منطقه → استان';
        }
        
        if (!$province) {
            return [
                'status' => 'unknown',
                'message' => 'اطلاعات منطقه جغرافیایی نامشخص است',
                'province_name' => null,
                'is_deprived' => null,
                'path' => $path
            ];
        }
        
        // بررسی وضعیت محرومیت
        $isDeprived = $province->is_deprived ?? false;
        $deprivationRank = $province->deprivation_rank ?? null;
        
        if ($isDeprived) {
            $status = 'none'; // قرمز - منطقه محروم
            $message = sprintf('منطقه محروم: %s (رتبه محرومیت: %s)', 
                $province->name, 
                $deprivationRank ? $deprivationRank : 'نامشخص'
            );
        } else {
            $status = 'complete'; // سبز - منطقه غیرمحروم
            $message = sprintf('منطقه غیرمحروم: %s', $province->name);
        }
        
        return [
            'status' => $status,
            'message' => $message,
            'province_name' => $province->name,
            'is_deprived' => $isDeprived,
            'deprivation_rank' => $deprivationRank,
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
} 