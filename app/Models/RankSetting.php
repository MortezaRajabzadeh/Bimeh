<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RankSetting extends Model
{
    use HasFactory;

    /**
     * فیلدهای قابل پر شدن
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'key',
        'description',
        'weight',
        'category',
        'is_active',
        'sort_order',
        'requires_document', // فیلد جدید
        // 'color',            // فیلد جدید - این خط کامنت شده است و نیازی به تغییر ندارد
    ];

    /**
     * فیلدهای تبدیل به مقادیر داده‌ای
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'weight' => 'integer',
        'sort_order' => 'integer',
        'requires_document' => 'boolean', // اضافه کردن cast برای فیلد جدید
    ];

    /**
     * رابطه با معیارهای خانواده‌ها
     */
    public function familyCriteria()
    {
        return $this->hasMany(FamilyCriterion::class);
    }
    
    /**
     * The families that belong to the rank setting.
     */
    public function families()
    {
        return $this->belongsToMany(Family::class, 'family_criteria', 'rank_setting_id', 'family_id')
                    ->withPivot(['has_criteria', 'notes'])
                    ->withTimestamps();
    }

    /**
     * رابطه با طرح‌های رتبه‌بندی
     */
    public function rankingSchemes()
    {
        return $this->belongsToMany(RankingScheme::class, 'ranking_scheme_criteria')
                    ->withPivot('weight')
                    ->withTimestamps();
    }

    /**
     * دریافت تمام معیارهای فعال
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * دریافت معیارها بر اساس دسته
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * مرتب‌سازی بر اساس ترتیب تعریف شده
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * دریافت تمام دسته‌های معیارها
     */
    public static function getCategories()
    {
        return [
            'disability' => 'ازکارافتادگی',
            'disease' => 'بیماری‌های خاص',
            'addiction' => 'اعتیاد',
            'economic' => 'وضعیت اقتصادی',
            'social' => 'وضعیت اجتماعی',
            'other' => 'سایر',
        ];
    }

    /**
     * دریافت نام فارسی دسته
     */
    public function getCategoryNameAttribute()
    {
        $categories = self::getCategories();
        return $categories[$this->category] ?? 'نامشخص';
    }
}
