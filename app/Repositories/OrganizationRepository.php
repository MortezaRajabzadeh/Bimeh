<?php

namespace App\Repositories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;

class OrganizationRepository extends BaseRepository
{
    /**
     * ایجاد نمونه رپوزیتوری
     */
    public function __construct(Organization $model)
    {
        parent::__construct($model);
    }

    /**
     * دریافت سازمان‌های فعال
     */
    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * دریافت سازمان‌های بیمه
     */
    public function getInsuranceCompanies(): Collection
    {
        return $this->model->insurance()->active()->get();
    }

    /**
     * دریافت سازمان‌های خیریه
     */
    public function getCharities(): Collection
    {
        return $this->model->charity()->active()->get();
    }

    /**
     * ذخیره لوگوی سازمان
     */
    public function saveLogo(Organization $organization, $file): string
    {
        $fileName = 'logo_' . $organization->id . '_' . time() . '.' . $file->extension();
        $path = $file->storeAs('organizations/logos', $fileName, 'public');
        
        $organization->update([
            'logo_path' => $path
        ]);
        
        return $path;
    }

    /**
     * جستجوی سازمان‌ها
     */
    public function search(string $term): Collection
    {
        return $this->model->where('name', 'like', "%{$term}%")
            ->orWhere('code', 'like', "%{$term}%")
            ->orWhere('email', 'like', "%{$term}%")
            ->orWhere('phone', 'like', "%{$term}%")
            ->get();
    }
} 