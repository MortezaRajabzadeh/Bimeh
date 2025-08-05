<?php

namespace App\QueryFilters;

use Spatie\QueryBuilder\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use App\Models\RankSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

/**
 * فیلتر سفارشی برای اعمال معیارهای رتبه‌بندی خانواده‌ها
 * 
 * استفاده: ?filter[ranking]=criteria_id1,criteria_id2&filter[ranking_weights]=5,3
 * یا: ?filter[ranking_scheme]=scheme_id
 */
class FamilyRankingFilter implements Filter
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
            Log::debug('🎯 Applying FamilyRankingFilter', [
                'property' => $property,
                'value' => $value,
                'value_type' => gettype($value)
            ]);

            // بررسی نوع فیلتر
            switch ($property) {
                case 'ranking':
                    return $this->applyRankingCriteria($query, $value);
                case 'ranking_scheme':
                    return $this->applyRankingScheme($query, $value);
                case 'ranking_score_min':
                    return $this->applyScoreFilter($query, $value, 'min');
                case 'ranking_score_max':
                    return $this->applyScoreFilter($query, $value, 'max');
                case 'ranking_score_range':
                    return $this->applyScoreRange($query, $value);
                default:
                    return $query;
            }

        } catch (\Exception $e) {
            Log::error('❌ Error in FamilyRankingFilter', [
                'property' => $property,
                'value' => $value,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $query;
        }
    }

    /**
     * اعمال معیارهای رتبه‌بندی
     */
    private function applyRankingCriteria(Builder $query, $value): Builder
    {
        // تبدیل value به آرایه
        $criteriaIds = $this->parseCriteriaIds($value);
        
        if (empty($criteriaIds)) {
            return $query;
        }

        // دریافت وزن‌های مربوط به معیارها
        $weights = $this->parseWeights(request('filter.ranking_weights', ''));
        
        // محاسبه امتیاز وزنی
        return $this->calculateWeightedScore($query, $criteriaIds, $weights);
    }

    /**
     * اعمال طرح رتبه‌بندی
     */
    private function applyRankingScheme(Builder $query, $schemeId): Builder
    {
        if (empty($schemeId)) {
            return $query;
        }

        // دریافت معیارهای طرح رتبه‌بندی
        $schemeCriteria = DB::table('ranking_scheme_criteria')
            ->where('ranking_scheme_id', $schemeId)
            ->pluck('weight', 'rank_setting_id')
            ->toArray();

        if (empty($schemeCriteria)) {
            return $query;
        }

        return $this->calculateWeightedScore($query, array_keys($schemeCriteria), array_values($schemeCriteria));
    }

    /**
     * اعمال فیلتر امتیاز
     */
    private function applyScoreFilter(Builder $query, $value, string $type): Builder
    {
        if (!is_numeric($value)) {
            return $query;
        }

        $score = (float) $value;
        
        // محاسبه امتیاز وزنی برای همه خانواده‌ها
        $query->addSelect([
            'weighted_score' => $this->getWeightedScoreSubquery()
        ]);

        if ($type === 'min') {
            return $query->having('weighted_score', '>=', $score);
        } else {
            return $query->having('weighted_score', '<=', $score);
        }
    }

    /**
     * اعمال محدوده امتیاز
     */
    private function applyScoreRange(Builder $query, $value): Builder
    {
        if (!is_string($value) || !str_contains($value, ',')) {
            return $query;
        }

        [$min, $max] = array_map('trim', explode(',', $value));
        
        if (!is_numeric($min) || !is_numeric($max)) {
            return $query;
        }

        // محاسبه امتیاز وزنی
        $query->addSelect([
            'weighted_score' => $this->getWeightedScoreSubquery()
        ]);

        return $query->havingBetween('weighted_score', [(float) $min, (float) $max]);
    }

    /**
     * محاسبه امتیاز وزنی و سورت
     */
    private function calculateWeightedScore(Builder $query, array $criteriaIds, array $weights): Builder
    {
        // ایجاد subquery برای محاسبه امتیاز وزنی
        $scoreSubquery = $this->getWeightedScoreSubquery($criteriaIds, $weights);

        // اضافه کردن امتیاز محاسبه شده به select
        $query->addSelect([
            'weighted_score' => $scoreSubquery
        ]);

        // مرتب‌سازی بر اساس امتیاز (نزولی) - خانواده‌های با امتیاز بالاتر اول
        return $query->orderBy('weighted_score', 'desc');
    }

    /**
     * ایجاد subquery برای محاسبه امتیاز وزنی
     */
    private function getWeightedScoreSubquery(array $criteriaIds = [], array $weights = []): \Closure
    {
        return function ($query) use ($criteriaIds, $weights) {
            $query->selectRaw('
                COALESCE(
                    (
                        SELECT SUM(
                            CASE 
                                WHEN rs.weight IS NOT NULL THEN rs.weight
                                ELSE 0
                            END
                        )
                        FROM rank_settings rs
                        WHERE rs.id IN (' . implode(',', array_fill(0, count($criteriaIds), '?')) . ')
                        AND JSON_CONTAINS(families.acceptance_criteria, CAST(rs.id AS JSON))
                    ), 0
                ) as calculated_score
            ', $criteriaIds);
        };
    }

    /**
     * تبدیل value به آرایه معیارها
     */
    private function parseCriteriaIds($value): array
    {
        if (is_array($value)) {
            return array_filter($value, 'is_numeric');
        }

        if (is_string($value)) {
            return array_filter(
                array_map('trim', explode(',', $value)),
                'is_numeric'
            );
        }

        return [];
    }

    /**
     * تبدیل وزن‌ها به آرایه
     */
    private function parseWeights($weightsString): array
    {
        if (empty($weightsString)) {
            return [];
        }

        if (is_array($weightsString)) {
            return array_filter($weightsString, 'is_numeric');
        }

        if (is_string($weightsString)) {
            return array_filter(
                array_map('trim', explode(',', $weightsString)),
                'is_numeric'
            );
        }

        return [];
    }
} 