<?php

namespace App\Services;

use App\Models\Family;
use App\Models\Member;
use App\Models\Organization;
use App\Models\User;
use App\Repositories\FamilyRepository;
use App\Repositories\MemberRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class FamilyService
{
    protected FamilyRepository $familyRepository;
    protected MemberRepository $memberRepository;

    /**
     * ایجاد نمونه سرویس
     */
    public function __construct(
        FamilyRepository $familyRepository,
        MemberRepository $memberRepository
    ) {
        $this->familyRepository = $familyRepository;
        $this->memberRepository = $memberRepository;
    }

    /**
     * ثبت خانواده جدید
     */
    public function registerFamily(array $data, ?User $user = null): Family
    {
        // ثبت کاربر معرف در صورت وجود
        if ($user) {
            $data['registered_by'] = $user->id;
            
            // اگر کاربر از طرف یک خیریه است، خیریه را نیز ثبت می‌کنیم
            if ($user->isCharity() && $user->organization_id) {
                $data['charity_id'] = $user->organization_id;
            }
        }
        
        // وضعیت اولیه
        $data['status'] = 'pending';
        
        // ایجاد خانواده
        return $this->familyRepository->create($data);
    }

    /**
     * اضافه کردن عضو جدید به خانواده
     */
    public function addMember(Family $family, array $memberData): Member
    {
        return $this->memberRepository->createFamilyMember($family, $memberData);
    }

    /**
     * حذف عضو از خانواده
     */
    public function removeMember(Member $member): bool
    {
        // اگر سرپرست خانوار است، باید حداقل یک عضو دیگر وجود داشته باشد
        if ($member->is_head) {
            $familyMembersCount = $member->family->members()->count();
            
            if ($familyMembersCount <= 1) {
                throw new \Exception('سرپرست خانوار نمی‌تواند حذف شود مگر آنکه عضو دیگری وجود داشته باشد.');
            }
            
            // یک عضو دیگر را به عنوان سرپرست جدید انتخاب می‌کنیم
            $newHead = $member->family->members()->where('id', '!=', $member->id)->first();
            $newHead->update(['is_head' => true, 'relationship' => 'head']);
        }
        
        return $this->memberRepository->delete($member->id);
    }

    /**
     * تغییر وضعیت خانواده
     */
    public function changeStatus(Family $family, string $status, ?string $reason = null, ?Organization $insurance = null): Family
    {
        // اگر وضعیت به تایید تغییر کرده و سازمان بیمه تعیین شده است
        if ($status === 'approved' && $insurance) {
            $family->insurance_id = $insurance->id;
            $family->save();
        }
        
        return $this->familyRepository->changeStatus($family, $status, $reason);
    }

    /**
     * دریافت همه خانواده‌ها
     */
    public function getAllFamilies($perPage = 15): LengthAwarePaginator
    {
        return $this->familyRepository->paginate($perPage);
    }

    /**
     * دریافت خانواده‌ها بر اساس وضعیت
     */
    public function getFamiliesByStatus(string $status, $perPage = 15): LengthAwarePaginator
    {
        switch ($status) {
            case 'pending':
                return $this->familyRepository->getPending($perPage);
            case 'reviewing':
                return $this->familyRepository->getReviewing($perPage);
            case 'approved':
                return $this->familyRepository->getApproved($perPage);
            case 'rejected':
                return $this->familyRepository->getRejected($perPage);
            default:
                return $this->getAllFamilies($perPage);
        }
    }

    /**
     * دریافت خانواده‌های مرتبط با یک کاربر
     */
    public function getFamiliesForUser(User $user, $perPage = 15): LengthAwarePaginator
    {
        // کاربر ادمین - دسترسی به همه خانواده‌ها
        if ($user->isAdmin()) {
            return $this->getAllFamilies($perPage);
        }
        
        // کاربر خیریه - دسترسی به خانواده‌های معرفی شده توسط خیریه
        if ($user->isCharity() && $user->organization_id) {
            return $this->familyRepository->getByCharity($user->organization, $perPage);
        }
        
        // کاربر بیمه - دسترسی به خانواده‌های تحت پوشش بیمه
        if ($user->isInsurance() && $user->organization_id) {
            return $this->familyRepository->getByInsurance($user->organization, $perPage);
        }
        
        // سایر کاربران - فقط خانواده‌های ثبت شده توسط خودشان
        return $this->familyRepository->getByUser($user, $perPage);
    }

    /**
     * جستجوی خانواده‌ها
     */
    public function searchFamilies(string $term, array $filters = [], $perPage = 15): LengthAwarePaginator
    {
        return $this->familyRepository->search($term, $filters, $perPage);
    }

    /**
     * دریافت آمار خانواده‌ها
     */
    public function getStatistics(): array
    {
        return $this->familyRepository->getStatistics();
    }
}