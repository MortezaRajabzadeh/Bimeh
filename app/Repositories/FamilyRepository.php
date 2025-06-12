<?php

namespace App\Repositories;

use App\Models\Family;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FamilyRepository extends BaseRepository
{
    /**
     * ایجاد نمونه رپوزیتوری
     */
    public function __construct(Family $model)
    {
        parent::__construct($model);
    }

    /**
     * دریافت خانواده‌های معرفی شده توسط یک سازمان خیریه
     */
    public function getByCharity(Organization $charity, $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('charity_id', $charity->id)
            ->with(['region', 'members'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * دریافت خانواده‌های تحت پوشش یک سازمان بیمه
     */
    public function getByInsurance(Organization $insurance, $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('insurance_id', $insurance->id)
            ->with(['region', 'members'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * دریافت خانواده‌های ثبت شده توسط یک کاربر
     */
    public function getByUser(User $user, $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('registered_by', $user->id)
            ->with(['region', 'members'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * دریافت خانواده‌های در انتظار بررسی
     */
    public function getPending($perPage = 15): LengthAwarePaginator
    {
        return $this->model->pending()
            ->with(['region', 'charity', 'members'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * دریافت خانواده‌های در حال بررسی
     */
    public function getReviewing($perPage = 15): LengthAwarePaginator
    {
        return $this->model->reviewing()
            ->with(['region', 'charity', 'members'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * دریافت خانواده‌های تایید شده
     */
    public function getApproved($perPage = 15): LengthAwarePaginator
    {
        return $this->model->approved()
            ->with(['region', 'charity', 'insurance', 'members'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * دریافت خانواده‌های رد شده
     */
    public function getRejected($perPage = 15): LengthAwarePaginator
    {
        return $this->model->rejected()
            ->with(['region', 'charity', 'members'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * جستجو در خانواده‌ها
     */
    public function search(string $term, array $filters = [], $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->where(function($q) use ($term) {
            $q->where('family_code', 'like', "%{$term}%")
              ->orWhere('address', 'like', "%{$term}%")
              ->orWhere('postal_code', 'like', "%{$term}%")
              ->orWhereHas('members', function($q) use ($term) {
                  $q->where('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('national_code', 'like', "%{$term}%");
              });
        });
        
        // اعمال فیلترها
        if (!empty($filters)) {
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            
            if (isset($filters['region_id'])) {
                $query->where('region_id', $filters['region_id']);
            }
            
            if (isset($filters['charity_id'])) {
                $query->where('charity_id', $filters['charity_id']);
            }
            
            if (isset($filters['insurance_id'])) {
                $query->where('insurance_id', $filters['insurance_id']);
            }
            
            if (isset($filters['poverty_confirmed'])) {
                $query->where('poverty_confirmed', $filters['poverty_confirmed']);
            }
        }
        
        return $query->with(['region', 'charity', 'insurance', 'members'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * تغییر وضعیت خانواده
     */
    public function changeStatus(Family $family, string $status, ?string $reason = null): Family
    {
        $data = ['status' => $status];
        
        if ($status === 'approved') {
            $data['verified_at'] = now();
        } elseif ($status === 'rejected' && $reason) {
            $data['rejection_reason'] = $reason;
        }
        
        $family->update($data);
        
        return $family->fresh();
    }

    /**
     * دریافت آمار کلی
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->model->count(),
            'pending' => $this->model->pending()->count(),
            'reviewing' => $this->model->reviewing()->count(),
            'approved' => $this->model->approved()->count(),
            'rejected' => $this->model->rejected()->count(),
        ];
    }


// اضافه کردن متدهای جدید به FamilyRepository

/**
 * دریافت خانواده‌ها با فیلترهای پیشرفته
 */
public function getFilteredFamilies(array $filters = [], string $sortField = 'created_at', string $sortDirection = 'desc', int $perPage = 15): LengthAwarePaginator
{
    $query = $this->model->with([
        'province:id,name',
        'city:id,name',
        'charity:id,name',
        'members:id,family_id,first_name,last_name,national_code,is_head,relationship,problem_type,occupation'
    ]);

    // Count only final insurances
    $query->withCount(['insurances as final_insurances_count' => function ($query) {
        $query->where('status', 'insured');
    }]);

    // Apply tab filtering
    if (isset($filters['activeTab'])) {
        $this->applyTabFilter($query, $filters['activeTab']);
    }

    // Apply sorting
    $this->applySorting($query, $sortField, $sortDirection, $filters['appliedSchemeId'] ?? null);

    // Apply advanced filters
    $this->applyAdvancedFilters($query, $filters);

    return $query->paginate($perPage);
}

/**
 * اعمال فیلتر تب‌ها
 */
private function applyTabFilter($query, string $activeTab): void
{
    switch ($activeTab) {
        case 'pending':
            $query->where('status', '!=', 'deleted')
                  ->where('wizard_status', \App\Enums\InsuranceWizardStep::PENDING->value);
            break;
        case 'reviewing':
            $query->where('status', '!=', 'deleted')
                  ->where('wizard_status', \App\Enums\InsuranceWizardStep::REVIEWING->value);
            break;
        case 'approved':
            $query->where('status', '!=', 'deleted')
                  ->where('wizard_status', \App\Enums\InsuranceWizardStep::APPROVED->value);
            break;
        case 'excel':
            $query->where('status', '!=', 'deleted')
                  ->where('wizard_status', \App\Enums\InsuranceWizardStep::EXCEL_UPLOAD->value);
            break;
        case 'insured':
            $query->where('status', '!=', 'deleted')
                  ->where('wizard_status', \App\Enums\InsuranceWizardStep::INSURED->value);
            break;
        case 'deleted':
            $query->where('status', 'deleted');
            break;
        default:
            $query->where('status', '!=', 'deleted')
                  ->where('wizard_status', \App\Enums\InsuranceWizardStep::PENDING->value);
            break;
    }
}

/**
 * اعمال مرتب‌سازی هوشمند
 */
private function applySorting($query, string $sortField, string $sortDirection, ?int $appliedSchemeId): void
{
    if ($sortField === 'calculated_score' && $appliedSchemeId) {
        // همیشه امتیازات را به صورت نزولی مرتب کنیم تا خانواده‌های با مشکلات بیشتر (امتیاز بالاتر) در اولویت قرار گیرند
        $this->applyCalculatedScoreSorting($query, 'desc', $appliedSchemeId);
    } elseif ($sortField === 'insurance_payer') {
        $this->applyInsurancePayerSorting($query, $sortDirection);
    } elseif ($sortField === 'insurance_type') {
        $this->applyInsuranceTypeSorting($query, $sortDirection);
    } elseif ($sortField === 'family_head') {
        $this->applyFamilyHeadSorting($query, $sortDirection);
    } else {
        $query->orderBy($sortField, $sortDirection);
    }
}

/**
 * مرتب‌سازی بر اساس امتیاز محاسبه شده
 */
private function applyCalculatedScoreSorting($query, string $sortDirection, int $schemeId): void
{
    $query->select('families.*')
          ->selectRaw('
              SUM(
                  CASE
                      WHEN rsc.weight IS NOT NULL THEN rsc.weight
                      ELSE rs.weight
                  END
              ) as calculated_score
          ')
          ->leftJoin('family_criteria as fc', 'families.id', '=', 'fc.family_id')
          ->leftJoin('rank_settings as rs', function($join) {
              $join->on('fc.rank_setting_id', '=', 'rs.id')
                   ->where('rs.is_active', true);
          })
          ->leftJoin('ranking_scheme_criteria as rsc', function ($join) use ($schemeId) {
              $join->on('rs.id', '=', 'rsc.rank_setting_id')
                   ->where('rsc.ranking_scheme_id', '=', $schemeId);
          })
          ->groupBy('families.id')
          ->orderBy('calculated_score', $sortDirection);
}

/**
 * مرتب‌سازی بر اساس پرداخت‌کننده بیمه
 */
private function applyInsurancePayerSorting($query, string $sortDirection): void
{
    $query->leftJoin('family_insurances', 'families.id', '=', 'family_insurances.family_id')
          ->where(function ($q) {
              $q->where('family_insurances.status', 'insured')
                ->orWhereNull('family_insurances.id');
          })
          ->orderBy('family_insurances.insurance_payer', $sortDirection)
          ->select('families.*');
}

/**
 * مرتب‌سازی بر اساس نوع بیمه
 */
private function applyInsuranceTypeSorting($query, string $sortDirection): void
{
    $query->leftJoin('family_insurances', 'families.id', '=', 'family_insurances.family_id')
          ->where(function ($q) {
              $q->where('family_insurances.status', 'insured')
                ->orWhereNull('family_insurances.id');
          })
          ->orderBy('family_insurances.insurance_type', $sortDirection)
          ->select('families.*');
}

/**
 * مرتب‌سازی بر اساس سرپرست خانواده
 */
private function applyFamilyHeadSorting($query, string $sortDirection): void
{
    $query->join('family_members as heads', function ($join) {
              $join->on('families.id', '=', 'heads.family_id')
                   ->where('heads.is_head', true);
          })
          ->orderBy('heads.first_name', $sortDirection)
          ->orderBy('heads.last_name', $sortDirection)
          ->select('families.*');
}

/**
 * اعمال فیلترهای پیشرفته
 */
private function applyAdvancedFilters($query, array $filters): void
{
    // جستجو
    if (!empty($filters['search'])) {
        $query->where(function ($q) use ($filters) {
            $q->where('family_code', 'like', '%' . $filters['search'] . '%')
              ->orWhere('address', 'like', '%' . $filters['search'] . '%')
              ->orWhere('additional_info', 'like', '%' . $filters['search'] . '%')
              ->orWhereHas('members', function ($memberQuery) use ($filters) {
                  $memberQuery->where('first_name', 'like', '%' . $filters['search'] . '%')
                             ->orWhere('last_name', 'like', '%' . $filters['search'] . '%')
                             ->orWhere('national_code', 'like', '%' . $filters['search'] . '%');
              });
        });
    }

    // فیلتر وضعیت
    if (!empty($filters['status'])) {
        if ($filters['status'] === 'insured') {
            $query->where(function($q) {
                $q->where('is_insured', true)
                  ->orWhere('status', 'insured');
            });
        } elseif ($filters['status'] === 'uninsured') {
            $query->where('is_insured', false)
                  ->where('status', '!=', 'insured');
        } else {
            $query->where('status', $filters['status']);
        }
    }

    // فیلتر استان
    if (!empty($filters['province_id'])) {
        $query->where('province_id', $filters['province_id']);
    }

    // فیلتر شهر
    if (!empty($filters['city_id'])) {
        $query->where('city_id', $filters['city_id']);
    }

    // فیلتر منطقه
    if (!empty($filters['district_id'])) {
        $query->where('district_id', $filters['district_id']);
    }

    // فیلتر خیریه
    if (!empty($filters['charity_id'])) {
        $query->where('charity_id', $filters['charity_id']);
    }

    // فیلتر رتبه خانواده
    if (!empty($filters['family_rank_range'])) {
        $rangeParts = explode('-', $filters['family_rank_range']);
        if (count($rangeParts) == 2) {
            $minRank = (int)$rangeParts[0];
            $maxRank = (int)$rangeParts[1];
            $query->whereBetween('family_rank', [$minRank, $maxRank]);
        }
    }

    // فیلتر معیارهای خاص
    if (!empty($filters['specific_criteria'])) {
        $criteriaIds = array_filter(explode(',', $filters['specific_criteria']));
        if (!empty($criteriaIds)) {
            $query->whereHas('criteria', function ($q) use ($criteriaIds) {
                $q->whereIn('rank_setting_id', $criteriaIds);
            });
        }
    }
}

/**
 * شمارش اعضای خانواده‌های انتخاب شده
 */
public function getTotalMembersCount(array $familyIds): int
{
    return \App\Models\Member::whereIn('family_id', $familyIds)->count();
}

/**
 * تست فیلترها
 */
public function testFilters(array $tempFilters): int
{
    $query = $this->model->query();
    
    foreach ($tempFilters as $filter) {
        if (empty($filter['value'])) continue;
        
        switch ($filter['type']) {
            case 'status':
                if ($filter['value'] === 'insured') {
                    $query->where(function($q) {
                        $q->where('is_insured', true)
                          ->orWhere('status', 'insured');
                    });
                } elseif ($filter['value'] === 'uninsured') {
                    $query->where('is_insured', false)
                          ->where('status', '!=', 'insured');
                } else {
                    $query->where('status', $filter['value']);
                }
                break;
            case 'province':
                $query->where('province_id', $filter['value']);
                break;
            case 'city':
                $query->where('city_id', $filter['value']);
                break;
            case 'district':
                $query->where('district_id', $filter['value']);
                break;
            case 'charity':
                $query->where('charity_id', $filter['value']);
                break;
        }
    }
    
    return $query->count();
}
 }
