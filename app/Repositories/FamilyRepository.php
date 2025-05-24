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
} 