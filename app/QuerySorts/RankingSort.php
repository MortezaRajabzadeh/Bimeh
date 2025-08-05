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
class RankingSort implements Sort
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
            Log::debug('📊 Applying RankingSort', [
                'property' => $property,
                'descending' => $descending
            ]);

            // دریافت طرح رتبه‌بندی فعال از درخواست
            $schemeId = request('ranking_scheme_id') ?? request('filter.ranking_scheme');
            
            if ($schemeId) {
                return $this->applySchemeBased($query, $schemeId, $descending);
            }

            // در صورت عدم وجود طرح، از رتبه محاسبه شده استفاده کن
            return $this->applyCalculatedRank($query, $descending);

        } catch (\Exception $e) {
            Log::error('❌ Error in RankingSort', [
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
     * اعمال سورت بر اساس طرح رتبه‌بندی
     *
     * @param Builder $query
     * @param int $schemeId
     * @param bool $descending
     * @return Builder
     */
    protected function applySchemeBased(Builder $query, int $schemeId, bool $descending): Builder
    {
        Log::debug('🎯 Applying scheme-based ranking sort', [
            'scheme_id' => $schemeId,
            'descending' => $descending
        ]);

        // کش کردن طرح رتبه‌بندی برای بهبود عملکرد
        $scheme = Cache::remember("ranking_scheme_{$schemeId}", 300, function () use ($schemeId) {
            return RankingScheme::with('criteria')->find($schemeId);
        });

        if (!$scheme || $scheme->criteria->isEmpty()) {
            Log::warning('⚠️ Ranking scheme not found or has no criteria', ['scheme_id' => $schemeId]);
            return $this->applyCalculatedRank($query, $descending);
        }

        // ساخت کوئری محاسبه امتیاز وزن‌دار
        return $this->buildWeightedScoreQuery($query, $scheme, $descending);
    }

    /**
     * ساخت کوئری محاسبه امتیاز وزن‌دار
     *
     * @param Builder $query
     * @param RankingScheme $scheme
     * @param bool $descending
     * @return Builder
     */
    protected function buildWeightedScoreQuery(Builder $query, RankingScheme $scheme, bool $descending): Builder
    {
        // ساخت subquery برای محاسبه امتیاز
        $criteriaWeights = [];
        foreach ($scheme->criteria as $criteria) {
            $weight = $criteria->pivot->weight ?? 1;
            $criteriaWeights[$criteria->id] = $weight;
        }

        // اضافه کردن join برای محاسبه امتیاز
        $query->leftJoin('family_criteria as fc_rank', function ($join) use ($criteriaWeights) {
            $join->on('families.id', '=', 'fc_rank.family_id')
                 ->whereIn('fc_rank.rank_setting_id', array_keys($criteriaWeights))
                 ->where('fc_rank.has_criteria', true);
        });

        // ساخت کیس‌های وزن‌دهی
        $weightCases = [];
        foreach ($criteriaWeights as $criteriaId => $weight) {
            $weightCases[] = "WHEN fc_rank.rank_setting_id = {$criteriaId} THEN {$weight}";
        }
        
        $weightCasesSql = implode(' ', $weightCases);
        
        // اضافه کردن ستون امتیاز محاسبه شده و سورت
        $query->selectRaw("
            families.*,
            COALESCE(SUM(
                CASE 
                    {$weightCasesSql}
                    ELSE 0 
                END
            ), 0) as final_weighted_score
        ")
        ->groupBy('families.id')
        ->orderBy('final_weighted_score', $descending ? 'desc' : 'asc');

        Log::debug('✅ Weighted score query built', [
            'criteria_count' => count($criteriaWeights),
            'descending' => $descending
        ]);

        return $query;
    }

    /**
     * اعمال سورت بر اساس رتبه محاسبه شده
     *
     * @param Builder $query
     * @param bool $descending
     * @return Builder
     */
    protected function applyCalculatedRank(Builder $query, bool $descending): Builder
    {
        Log::debug('📈 Applying calculated rank sort', ['descending' => $descending]);
        
        $direction = $descending ? 'desc' : 'asc';
        
        // سورت بر اساس رتبه محاسبه شده، سپس تاریخ ایجاد
        return $query->orderBy('calculated_rank', $direction)
                    ->orderBy('created_at', 'desc');
    }
}
