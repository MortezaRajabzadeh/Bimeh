<?php

namespace App\QuerySorts;

use Spatie\QueryBuilder\Sorts\Sort;
use Illuminate\Database\Eloquent\Builder;
use App\Models\RankSetting;
use App\Models\RankingScheme;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * سورت سفارشی برای رتبه‌بندی وزن‌دار خانواده‌ها
 * 
 * استفاده: ?sort=weighted_rank یا ?sort=-weighted_rank
 */
class FamilyRankingSort implements Sort
{
    /**
     * اعمال سورت وزن‌دار به کوئری
     *
     * @param Builder $query
     * @param bool $descending
     * @param string $property
     * @return Builder
     */
    public function __invoke(Builder $query, bool $descending, string $property): Builder
    {
        try {
            Log::debug('📊 Applying FamilyRankingSort', [
                'property' => $property,
                'descending' => $descending
            ]);

            // بررسی نوع سورت
            switch ($property) {
                case 'weighted_rank':
                    return $this->applyWeightedRankSort($query, $descending);
                case 'criteria_count':
                    return $this->applyCriteriaCountSort($query, $descending);
                case 'priority_score':
                    return $this->applyPriorityScoreSort($query, $descending);
                default:
                    return $query;
            }

        } catch (\Exception $e) {
            Log::error('❌ Error in FamilyRankingSort', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'property' => $property
            ]);
            
            // در صورت خطا، سورت ساده بر اساس رتبه محاسبه شده
            $direction = $descending ? 'desc' : 'asc';
            return $query->orderBy('calculated_rank', $direction);
        }
    }

    /**
     * اعمال سورت بر اساس امتیاز وزنی
     */
    private function applyWeightedRankSort(Builder $query, bool $descending): Builder
    {
        // دریافت معیارهای فعال از کش یا دیتابیس
        $activeCriteria = $this->getActiveCriteria();
        
        if (empty($activeCriteria)) {
            return $query;
        }

        // محاسبه امتیاز وزنی
        $scoreSubquery = $this->getWeightedScoreSubquery($activeCriteria);
        
        $query->addSelect([
            'weighted_score' => $scoreSubquery
        ]);

        $direction = $descending ? 'desc' : 'asc';
        return $query->orderBy('weighted_score', $direction);
    }

    /**
     * اعمال سورت بر اساس تعداد معیارها
     */
    private function applyCriteriaCountSort(Builder $query, bool $descending): Builder
    {
        $query->addSelect([
            'criteria_count' => function ($subQuery) {
                $subQuery->selectRaw('
                    JSON_LENGTH(COALESCE(families.acceptance_criteria, "[]"))
                ');
            }
        ]);

        $direction = $descending ? 'desc' : 'asc';
        return $query->orderBy('criteria_count', $direction);
    }

    /**
     * اعمال سورت بر اساس امتیاز اولویت
     */
    private function applyPriorityScoreSort(Builder $query, bool $descending): Builder
    {
        // محاسبه امتیاز اولویت شامل:
        // 1. امتیاز وزنی معیارها
        // 2. تعداد اعضای خانواده
        // 3. وضعیت سرپرست (زن سرپرست = امتیاز بیشتر)
        // 4. تاریخ ثبت (قدیمی‌تر = امتیاز بیشتر)
        
        $prioritySubquery = function ($subQuery) {
            $subQuery->selectRaw('
                (
                    COALESCE(
                        (
                            SELECT SUM(rs.weight)
                            FROM rank_settings rs
                            WHERE JSON_CONTAINS(families.acceptance_criteria, CAST(rs.id AS JSON))
                            AND rs.is_active = 1
                        ), 0
                    ) * 10
                ) + 
                (
                    SELECT COUNT(*) * 2
                    FROM family_members fm
                    WHERE fm.family_id = families.id
                ) +
                (
                    CASE 
                        WHEN EXISTS (
                            SELECT 1 FROM family_members fm 
                            WHERE fm.family_id = families.id 
                            AND fm.is_head = 1 
                            AND fm.gender = "female"
                        ) THEN 50
                        ELSE 0
                    END
                ) +
                (
                    CASE 
                        WHEN families.created_at <= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 20
                        WHEN families.created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 10
                        ELSE 0
                    END
                ) as priority_score
            ');
        };

        $query->addSelect([
            'priority_score' => $prioritySubquery
        ]);

        $direction = $descending ? 'desc' : 'asc';
        return $query->orderBy('priority_score', $direction);
    }

    /**
     * دریافت معیارهای فعال
     */
    private function getActiveCriteria(): array
    {
        return Cache::remember('active_ranking_criteria', 3600, function () {
            return RankSetting::where('is_active', true)
                ->orderBy('weight', 'desc')
                ->pluck('weight', 'id')
                ->toArray();
        });
    }

    /**
     * ایجاد subquery برای محاسبه امتیاز وزنی
     */
    private function getWeightedScoreSubquery(array $criteria): \Closure
    {
        return function ($query) use ($criteria) {
            $criteriaIds = array_keys($criteria);
            $placeholders = implode(',', array_fill(0, count($criteriaIds), '?'));
            
            $query->selectRaw('
                COALESCE(
                    (
                        SELECT SUM(rs.weight)
                        FROM rank_settings rs
                        WHERE rs.id IN (' . $placeholders . ')
                        AND JSON_CONTAINS(families.acceptance_criteria, CAST(rs.id AS JSON))
                        AND rs.is_active = 1
                    ), 0
                ) as calculated_score
            ', $criteriaIds);
        };
    }
} 