<?php

namespace App\Repositories;

use App\Models\Family;
use App\Models\Member;
use Illuminate\Database\Eloquent\Collection;

class MemberRepository extends BaseRepository
{
    /**
     * ایجاد نمونه رپوزیتوری
     */
    public function __construct(Member $model)
    {
        parent::__construct($model);
    }

    /**
     * دریافت اعضای یک خانواده
     */
    public function getByFamily(Family $family): Collection
    {
        return $this->model->where('family_id', $family->id)->get();
    }

    /**
     * دریافت سرپرست خانوار
     */
    public function getFamilyHead(Family $family): ?Member
    {
        return $this->model->where('family_id', $family->id)
            ->where('is_head', true)
            ->first();
    }

    /**
     * ایجاد و تنظیم یک عضو به عنوان سرپرست خانوار
     */
    public function createAsHead(Family $family, array $data): Member
    {
        // ابتدا بررسی می‌کنیم آیا سرپرست دیگری وجود دارد
        $existingHead = $this->getFamilyHead($family);
        
        if ($existingHead) {
            // سرپرست قبلی را به عضو عادی تبدیل می‌کنیم
            $existingHead->update(['is_head' => false]);
        }
        
        // ایجاد عضو جدید به عنوان سرپرست
        $data['family_id'] = $family->id;
        $data['is_head'] = true;
        $data['relationship'] = 'head';
        
        return $this->create($data);
    }

    /**
     * ایجاد عضو جدید خانواده
     */
    public function createFamilyMember(Family $family, array $data): Member
    {
        $data['family_id'] = $family->id;
        
        // اگر قرار است عضو جدید سرپرست خانوار باشد
        if (isset($data['is_head']) && $data['is_head']) {
            return $this->createAsHead($family, $data);
        }
        
        return $this->create($data);
    }

    /**
     * جستجو بر اساس کد ملی
     */
    public function findByNationalCode(string $nationalCode): ?Member
    {
        return $this->model->where('national_code', $nationalCode)->first();
    }

    /**
     * دریافت آمار اعضا
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->model->count(),
            'withDisability' => $this->model->withDisability()->count(),
            'withChronicDisease' => $this->model->withChronicDisease()->count(),
            'employed' => $this->model->employed()->count(),
            'insured' => $this->model->insured()->count(),
            'uninsured' => $this->model->uninsured()->count(),
        ];
    }
} 