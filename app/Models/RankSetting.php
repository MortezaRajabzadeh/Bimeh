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
    ];

    /**
     * رابطه با معیارهای خانواده‌ها
     */
    public function familyCriteria()
    {
        return $this->hasMany(FamilyCriterion::class);
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
