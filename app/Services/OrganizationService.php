<?php

namespace App\Services;

use App\Models\Organization;
use App\Repositories\OrganizationRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class OrganizationService
{
    protected OrganizationRepository $organizationRepository;

    /**
     * ایجاد نمونه سرویس
     */
    public function __construct(OrganizationRepository $organizationRepository)
    {
        $this->organizationRepository = $organizationRepository;
    }

    /**
     * ایجاد سازمان جدید
     */
    public function createOrganization(array $data, ?UploadedFile $logo = null): Organization
    {
        $organization = $this->organizationRepository->create($data);
        
        if ($logo) {
            $this->updateLogo($organization, $logo);
        }
        
        return $organization;
    }

    /**
     * به‌روزرسانی سازمان
     */
    public function updateOrganization(Organization $organization, array $data, ?UploadedFile $logo = null): Organization
    {
        $this->organizationRepository->update($data, $organization->id);
        
        if ($logo) {
            $this->updateLogo($organization, $logo);
        }
        
        return $organization->fresh();
    }

    /**
     * آپلود و به‌روزرسانی لوگوی سازمان
     */
    public function updateLogo(Organization $organization, UploadedFile $logo): string
    {
        // حذف لوگوی قبلی در صورت وجود
        if ($organization->logo_path && Storage::disk('public')->exists($organization->logo_path)) {
            Storage::disk('public')->delete($organization->logo_path);
        }
        
        // ذخیره لوگوی جدید
        return $this->organizationRepository->saveLogo($organization, $logo);
    }

    /**
     * تغییر وضعیت فعال بودن سازمان
     */
    public function toggleActive(Organization $organization): Organization
    {
        $organization->is_active = !$organization->is_active;
        $organization->save();
        
        return $organization;
    }

    /**
     * دریافت همه سازمان‌ها
     */
    public function getAllOrganizations(): Collection
    {
        return $this->organizationRepository->all();
    }

    /**
     * دریافت سازمان‌های بیمه
     */
    public function getInsuranceCompanies(): Collection
    {
        return $this->organizationRepository->getInsuranceCompanies();
    }

    /**
     * دریافت سازمان‌های خیریه
     */
    public function getCharities(): Collection
    {
        return $this->organizationRepository->getCharities();
    }

    /**
     * دریافت سازمان‌های فعال
     */
    public function getActiveOrganizations(): Collection
    {
        return $this->organizationRepository->getActive();
    }

    /**
     * جستجوی سازمان‌ها
     */
    public function searchOrganizations(string $term): Collection
    {
        return $this->organizationRepository->search($term);
    }
} 
