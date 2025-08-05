<?php

namespace App\Http\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * فیلتر پیشرفته برای خانواده‌ها
 * سازگار با modal فیلتر موجود در families-approval.blade.php
 *
 * فیلدهای قابل فیلتر:
 * - status: وضعیت خانواده (insured, uninsured, pending, approved, rejected)
 * - province: استان (کد استان)
 * - city: شهر (کد شهر)
 * - deprivation_rank: رتبه محرومیت (high, medium, low یا عدد مستقیم)
 * - charity: خیریه معرف (کد خیریه)
 * - members_count: تعداد اعضا (با پشتیبانی از عملگرهای مقایسه)
 * - created_at: تاریخ ایجاد (با پشتیبانی از عملگرهای مقایسه)
 * - weighted_score: امتیاز وزنی (با پشتیبانی از محدوده)
 * - special_disease: وجود بیماری خاص (true/false)
 */
class FamilyAdvancedFilter implements Filter
{
    public function __invoke(Builder $query, $value, string $property): Builder
    {
        try {
            Log::info('🔍 FamilyAdvancedFilter::اعمال فیلتر', [
                'property' => $property,
                'value' => $value,
                'timestamp' => now()->format('Y-m-d H:i:s.u')
            ]);

            // اگر مقدار خالی است، کوئری را بدون تغییر برگردان
            if (empty($value) && $value !== '0' && $value !== 0 && $value !== false) {
                return $query;
            }

            switch ($property) {
                case 'status':
                    return $this->applyStatusFilter($query, $value);

                case 'province':
                    return $this->applyProvinceFilter($query, $value);

                case 'city':
                    return $this->applyCityFilter($query, $value);

                case 'deprivation_rank':
                    return $this->applyDeprivationRankFilter($query, $value);

                case 'charity':
                    return $this->applyCharityFilter($query, $value);

                case 'members_count':
                    return $this->applyMembersCountFilter($query, $value);

                case 'created_at':
                    return $this->applyCreatedAtFilter($query, $value);

                case 'weighted_score':
                    return $this->applyWeightedScoreFilter($query, $value);

                case 'special_disease':
                    return $this->applySpecialDiseaseFilter($query, $value);

                default:
                    Log::warning('⚠️ FamilyAdvancedFilter::فیلتر ناشناخته', [
                        'property' => $property,
                        'timestamp' => now()->format('Y-m-d H:i:s.u')
                    ]);
                    return $query;
            }
        } catch (\Exception $e) {
            Log::error('❌ FamilyAdvancedFilter::خطا در اعمال فیلتر', [
                'property' => $property,
                'value' => $value,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'timestamp' => now()->format('Y-m-d H:i:s.u')
            ]);

            return $query;
        }
    }

    /**
     * فیلتر وضعیت خانواده
     * مقادیر: insured, uninsured, pending, approved, rejected
     */
    private function applyStatusFilter(Builder $query, $value): Builder
    {
        if (is_array($value) && isset($value['operator']) && isset($value['value'])) {
            $operator = $value['operator'];
            $filterValue = $value['value'];

            switch ($operator) {
                case 'not_equals':
                    return $query->where('families.status', '!=', $filterValue);
                case 'equals':
                default:
                    return $query->where('families.status', $filterValue);
            }
        }

        return $query->where('families.status', $value);
    }

    /**
     * فیلتر استان
     */
    private function applyProvinceFilter(Builder $query, $value): Builder
    {
        if (is_array($value) && isset($value['operator']) && isset($value['value'])) {
            $operator = $value['operator'];
            $filterValue = $value['value'];

            switch ($operator) {
                case 'not_equals':
                    return $query->where('families.province_id', '!=', $filterValue);
                case 'equals':
                default:
                    return $query->where('families.province_id', $filterValue);
            }
        }

        return $query->where('families.province_id', $value);
    }

    /**
     * فیلتر شهر
     */
    private function applyCityFilter(Builder $query, $value): Builder
    {
        if (is_array($value) && isset($value['operator']) && isset($value['value'])) {
            $operator = $value['operator'];
            $filterValue = $value['value'];

            switch ($operator) {
                case 'not_equals':
                    return $query->where('families.city_id', '!=', $filterValue);
                case 'equals':
                default:
                    return $query->where('families.city_id', $filterValue);
            }
        }

        return $query->where('families.city_id', $value);
    }

    /**
     * فیلتر رتبه محرومیت
     * مقادیر: high (1-3), medium (4-6), low (7-10)
     */
    private function applyDeprivationRankFilter(Builder $query, $value): Builder
    {
        if (is_array($value) && isset($value['value'])) {
            $filterValue = $value['value'];
        } else {
            $filterValue = $value;
        }

        switch ($filterValue) {
            case 'high':
                return $query->whereBetween('families.deprivation_rank', [1, 3]);
            case 'medium':
                return $query->whereBetween('families.deprivation_rank', [4, 6]);
            case 'low':
                return $query->whereBetween('families.deprivation_rank', [7, 10]);
            default:
                return $query->where('families.deprivation_rank', $filterValue);
        }
    }

    /**
     * فیلتر خیریه معرف
     */
    private function applyCharityFilter(Builder $query, $value): Builder
    {
        if (is_array($value) && isset($value['operator']) && isset($value['value'])) {
            $operator = $value['operator'];
            $filterValue = $value['value'];

            switch ($operator) {
                case 'not_equals':
                    return $query->where('families.charity_id', '!=', $filterValue);
                case 'equals':
                default:
                    return $query->where('families.charity_id', $filterValue);
            }
        }

        return $query->where('families.charity_id', $value);
    }

    /**
     * فیلتر تعداد اعضا
     * پشتیبانی از عملگرهای: equals, not_equals, greater_than, less_than
     */
    private function applyMembersCountFilter(Builder $query, $value): Builder
    {
        if (is_array($value) && isset($value['operator']) && isset($value['value'])) {
            $operator = $value['operator'];
            $filterValue = $value['value'];

            switch ($operator) {
                case 'greater_than':
                    return $query->where('families.members_count', '>', $filterValue);
                case 'less_than':
                    return $query->where('families.members_count', '<', $filterValue);
                case 'not_equals':
                    return $query->where('families.members_count', '!=', $filterValue);
                case 'equals':
                default:
                    return $query->where('families.members_count', $filterValue);
            }
        }

        return $query->where('families.members_count', $value);
    }

    /**
     * فیلتر تاریخ (تاریخ پایان بیمه)
     * پشتیبانی از عملگرهای: equals, greater_than, less_than
     */
    private function applyCreatedAtFilter(Builder $query, $value): Builder
    {
        if (is_array($value) && isset($value['operator']) && isset($value['value'])) {
            $operator = $value['operator'];
            $filterValue = $value['value'];

            switch ($operator) {
                case 'greater_than':
                    return $query->where('families.created_at', '>', $filterValue);
                case 'less_than':
                    return $query->where('families.created_at', '<', $filterValue);
                case 'equals':
                default:
                    return $query->whereDate('families.created_at', $filterValue);
            }
        }

        return $query->whereDate('families.created_at', $value);
    }

    /**
     * فیلتر امتیاز وزنی
     * پشتیبانی از محدوده min/max
     */
    private function applyWeightedScoreFilter(Builder $query, $value): Builder
    {
        if (is_array($value)) {
            $min = $value['min'] ?? null;
            $max = $value['max'] ?? null;

            if ($min !== null && $max !== null) {
                return $query->whereBetween('families.weighted_score', [$min, $max]);
            } elseif ($min !== null) {
                return $query->where('families.weighted_score', '>=', $min);
            } elseif ($max !== null) {
                return $query->where('families.weighted_score', '<=', $max);
            }
        }

        return $query->where('families.weighted_score', $value);
    }

    /**
     * فیلتر بیماری خاص
     * مقادیر: true (دارد), false (ندارد)
     */
    private function applySpecialDiseaseFilter(Builder $query, $value): Builder
    {
        $hasSpecialDisease = $value === 'true' || $value === true;

        return $query->whereHas('members', function ($memberQuery) use ($hasSpecialDisease) {
            if ($hasSpecialDisease) {
                $memberQuery->where('has_special_disease', true);
            } else {
                $memberQuery->where(function($q) {
                    $q->where('has_special_disease', false)
                      ->orWhereNull('has_special_disease');
                });
            }
        });
    }
}
