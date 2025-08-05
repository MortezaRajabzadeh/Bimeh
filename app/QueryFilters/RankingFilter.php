<?php

namespace App\QueryFilters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\RankSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * فیلتر سفارشی برای اعمال معیارهای رتبه‌بندی
 * 
 * استفاده: ?filter[ranking]=criteria_id1,criteria_id2&filter[ranking_weights]=5,3
 */
class RankingFilter implements Filter
{
    /**
     * اعمال فیلتر رتبه‌بندی به کوئری
     *
     * @param Builder $query
     * @param mixed $value - آرایه یا رشته معیارهای انتخاب شده
     * @param string $property
     * @return Builder
     */
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        try {
            Log::debug('🎯 Applying RankingFilter', [
                'property' => $property,
                'value' => $value,
                'value_type' => gettype($value)
            ]);

            // تبدیل value به آرایه در صورت نیاز
            $criteriaIds = $this->parseCriteriaIds($value);
            
            if (empty($criteriaIds)) {
                Log::debug('⚠️ No valid criteria IDs provided');
                return $query;
            }

            // دریافت وزن‌های مربوط به معیارها از درخواست
            $weights = $this->parseWeights(request('filter.ranking_weights', ''));
            
            // اعمال فیلتر بر اساس معیارهای انتخاب شده
            return $this->applyRankingCriteria($query, $criteriaIds, $weights);

        } catch (\Exception $e) {
            Log::error('❌ Error in RankingFilter', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'value' => $value
            ]);
            
            // در صورت خطا، کوئری اصلی را بدون تغییر برگردان
            return $query;
        }
    }

    /**
     * تجزیه و تحلیل ID های معیارها
     *
     * @param mixed $value
     * @return array
     */
    protected function parseCriteriaIds($value): array
    {
        if (is_array($value)) {
            return array_filter(array_map('intval', $value));
        }
        
        if (is_string($value)) {
            return array_filter(array_map('intval', explode(',', $value)));
        }
        
        if (is_numeric($value)) {
            return [(int) $value];
        }
        
        return [];
    }

    /**
     * تجزیه و تحلیل وزن‌ها
     *
     * @param mixed $weightsValue
     * @return array
     */
    protected function parseWeights($weightsValue): array
    {
        if (is_array($weightsValue)) {
            return array_map('floatval', $weightsValue);
        }
        
        if (is_string($weightsValue) && !empty($weightsValue)) {
            return array_map('floatval', explode(',', $weightsValue));
        }
        
        return [];
    }

    /**
     * اعمال معیارهای رتبه‌بندی به کوئری
     *
     * @param Builder $query
     * @param array $criteriaIds
     * @param array $weights
     * @return Builder
     */
    protected function applyRankingCriteria(Builder $query, array $criteriaIds, array $weights): Builder
    {
        Log::debug('🎯 Applying ranking criteria', [
            'criteria_ids' => $criteriaIds,
            'weights' => $weights
        ]);

        // اضافه کردن جوین با pivot table
        $query->join('family_criteria', 'families.id', '=', 'family_criteria.family_id')
              ->whereIn('family_criteria.rank_setting_id', $criteriaIds)
              ->where('family_criteria.has_criteria', true);

        // اگر وزن‌ها تعریف شده باشند، محاسبه امتیاز وزن‌دار
        if (!empty($weights)) {
            $this->addWeightedScoreCalculation($query, $criteriaIds, $weights);
        }

        // گروه‌بندی برای جلوگیری از تکرار رکوردها
        $query->groupBy('families.id');

        return $query;
    }

    /**
     * اضافه کردن محاسبه امتیاز وزن‌دار
     *
     * @param Builder $query
     * @param array $criteriaIds
     * @param array $weights
     * @return void
     */
    protected function addWeightedScoreCalculation(Builder $query, array $criteriaIds, array $weights): void
    {
        // ساخت کیس‌های وزن‌دهی
        $weightCases = [];
        foreach ($criteriaIds as $index => $criteriaId) {
            $weight = $weights[$index] ?? 1;
            $weightCases[] = "WHEN family_criteria.rank_setting_id = {$criteriaId} THEN {$weight}";
        }
        
        $weightCasesSql = implode(' ', $weightCases);
        
        // اضافه کردن ستون امتیاز محاسبه شده
        $query->selectRaw("
            families.*,
            SUM(
                CASE 
                    {$weightCasesSql}
                    ELSE 1 
                END
            ) as calculated_weighted_score
        ");

        Log::debug('✅ Weighted score calculation added', [
            'weight_cases_count' => count($weightCases)
        ]);
    }
}
