<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyCriterion extends Model
{
    use HasFactory;

    /**
     * فیلدهای قابل پر شدن
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'family_id',
        'rank_setting_id',
        'has_criteria',
        'notes',
    ];

    /**
     * فیلدهای تبدیل به مقادیر داده‌ای
     *
     * @var array<string, string>
     */
    protected $casts = [
        'has_criteria' => 'boolean',
    ];

    /**
     * رابطه با خانواده
     */
    public function family()
    {
        return $this->belongsTo(Family::class);
    }

    /**
     * رابطه با تنظیمات رتبه
     */
    public function rankSetting()
    {
        return $this->belongsTo(RankSetting::class);
    }

    /**
     * دریافت معیارهای فعال
     */
    public function scopeActive($query)
    {
        return $query->where('has_criteria', true);
    }

    /**
     * دریافت معیارهای یک خانواده
     */
    public function scopeForFamily($query, $familyId)
    {
        return $query->where('family_id', $familyId);
    }
}
