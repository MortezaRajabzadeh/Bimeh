<?php

namespace App\Http\Sorts;

use Spatie\QueryBuilder\Sorts\Sort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * کلاس مرتب‌سازی پیشرفته برای خانواده‌ها
 * 
 * @package App\Http\Sorts
 * @author Laravel Super Starter
 */
class FamilyAdvancedSort implements Sort
{
    /**
     * اعمال مرتب‌سازی بر روی کوئری
     *
     * @param Builder $query
     * @param bool $descending
     * @param string $property
     * @return Builder
     */
    public function __invoke(Builder $query, bool $descending, string $property): Builder
    {
        try {
            // Log مرتب‌سازی درحال اعمال
            Log::info('🔄 FamilyAdvancedSort::اعمال مرتب‌سازی', [
                'property' => $property,
                'descending' => $descending,
                'timestamp' => now()->format('Y-m-d H:i:s.u')
            ]);

            $direction = $descending ? 'desc' : 'asc';

            switch ($property) {
                case 'family_code':
                    return $query->orderBy('families.family_code', $direction);

                case 'head_name':
                    return $query->leftJoin('members as head_member', function ($join) {
                        $join->on('families.id', '=', 'head_member.family_id')
                             ->where('head_member.relationship', '=', 'head');
                    })
                    ->orderBy('head_member.first_name', $direction)
                    ->orderBy('head_member.last_name', $direction);

                case 'province':
                    return $query->leftJoin('provinces', 'families.province_id', '=', 'provinces.id')
                                 ->orderBy('provinces.name', $direction);

                case 'city':
                    return $query->leftJoin('cities', 'families.city_id', '=', 'cities.id')
                                 ->orderBy('cities.name', $direction);

                case 'region':
                    return $query->leftJoin('regions', 'families.region_id', '=', 'regions.id')
                                 ->orderBy('regions.name', $direction);

                case 'charity':
                    return $query->leftJoin('organizations as charity_org', 'families.charity_id', '=', 'charity_org.id')
                                 ->orderBy('charity_org.name', $direction);

                case 'organization':
                    return $query->leftJoin('organizations', 'families.organization_id', '=', 'organizations.id')
                                 ->orderBy('organizations.name', $direction);

                case 'members_count':
                    return $query->withCount('members')
                                 ->orderBy('members_count', $direction);

                case 'insurance_status':
                    return $query->leftJoin('family_insurances as latest_insurance', function ($join) {
                        $join->on('families.id', '=', 'latest_insurance.family_id')
                             ->whereRaw('latest_insurance.id = (
                                 SELECT MAX(id) FROM family_insurances 
                                 WHERE family_id = families.id
                             )');
                    })
                    ->orderBy('latest_insurance.status', $direction);

                case 'insurance_amount':
                    return $query->leftJoin('family_insurances as amount_insurance', function ($join) {
                        $join->on('families.id', '=', 'amount_insurance.family_id')
                             ->whereRaw('amount_insurance.id = (
                                 SELECT MAX(id) FROM family_insurances 
                                 WHERE family_id = families.id
                             )');
                    })
                    ->orderBy('amount_insurance.premium_amount', $direction);

                case 'document_completeness':
                    return $this->sortByDocumentCompleteness($query, $direction);

                case 'identity_completeness':
                    return $this->sortByIdentityCompleteness($query, $direction);

                case 'deprived_area_status':
                    return $query->leftJoin('regions as deprived_regions', 'families.region_id', '=', 'deprived_regions.id')
                                 ->orderBy('deprived_regions.is_deprived', $direction);

                case 'created_at':
                    return $query->orderBy('families.created_at', $direction);

                case 'updated_at':
                    return $query->orderBy('families.updated_at', $direction);

                case 'wizard_status':
                    return $query->orderBy('families.wizard_status', $direction);

                default:
                    // مرتب‌سازی عمومی
                    return $query->orderBy("families.{$property}", $direction);
            }

        } catch (\Throwable $e) {
            Log::error('❌ خطا در FamilyAdvancedSort', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'property' => $property,
                'descending' => $descending
            ]);

            // در صورت خطا، مرتب‌سازی پیش‌فرض
            return $query->orderBy('families.created_at', 'desc');
        }
    }

    /**
     * مرتب‌سازی بر اساس کاملیت مدارک
     */
    private function sortByDocumentCompleteness(Builder $query, string $direction): Builder
    {
        return $query->leftJoin('documents', 'families.id', '=', 'documents.family_id')
                    ->selectRaw('
                        families.*,
                        CASE 
                            WHEN COUNT(CASE WHEN documents.type IN ("addiction", "unemployment", "illness") AND documents.status = "approved" THEN 1 END) >= 3 
                            THEN 1 
                            ELSE 0 
                        END as document_completeness_score
                    ')
                    ->groupBy('families.id')
                    ->orderBy('document_completeness_score', $direction);
    }

    /**
     * مرتب‌سازی بر اساس کاملیت اطلاعات هویتی
     */
    private function sortByIdentityCompleteness(Builder $query, string $direction): Builder
    {
        return $query->leftJoin('members', 'families.id', '=', 'members.family_id')
                    ->selectRaw('
                        families.*,
                        CASE 
                            WHEN COUNT(CASE WHEN members.first_name IS NULL OR members.first_name = "" OR members.last_name IS NULL OR members.last_name = "" OR members.national_code IS NULL OR members.national_code = "" THEN 1 END) = 0 
                            THEN 1 
                            ELSE 0 
                        END as identity_completeness_score
                    ')
                    ->groupBy('families.id')
                    ->orderBy('identity_completeness_score', $direction);
    }
}
