<?php

namespace App\Services;

use App\Repositories\FamilyRepository;
use Illuminate\Support\Facades\Cache;

class FamilyFilterService
{
    protected FamilyRepository $familyRepository;
    
    public function __construct(FamilyRepository $familyRepository)
    {
        $this->familyRepository = $familyRepository;
    }
    
    /**
     * دریافت خانواده‌ها با کش هوشمند
     */
    public function getFilteredFamiliesWithCache(array $filters, string $sortField, string $sortDirection, int $perPage, int $page): mixed
    {
        $cacheKey = $this->generateCacheKey($filters, $sortField, $sortDirection, $perPage, $page);
        
        return Cache::remember($cacheKey, 300, function () use ($filters, $sortField, $sortDirection, $perPage) {
            return $this->familyRepository->getFilteredFamilies($filters, $sortField, $sortDirection, $perPage);
        });
    }
    
    /**
     * تولید کلید کش
     */
    private function generateCacheKey(array $filters, string $sortField, string $sortDirection, int $perPage, int $page): string
    {
        $filterHash = md5(serialize($filters));
        return "families_filtered_{$filterHash}_{$sortField}_{$sortDirection}_{$perPage}_{$page}";
    }
    
    /**
     * پاکسازی کش مرتبط
     */
    public function clearRelatedCache(): void
    {
        $tags = ['families', 'families_filtered'];
        Cache::tags($tags)->flush();
    }
}
