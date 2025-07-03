<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\Member;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestFamilyValidation extends Command
{
    /**
     * نام دستور
     *
     * @var string
     */
    protected $signature = 'test:family-validation {--family_id= : شناسه خانواده برای تست (اختیاری)}';

    /**
     * توضیحات دستور
     *
     * @var string
     */
    protected $description = 'تست منطق اعتبارسنجی تکمیل اطلاعات اعضای خانواده برای رفع مشکل نمایش درصد صفر';

    /**
     * اجرای دستور
     *
     * @return int
     */
    public function handle()
    {
        $this->info('شروع تست اعتبارسنجی تکمیل اطلاعات اعضای خانواده');

        // بررسی وجود شناسه خانواده
        $familyId = $this->option('family_id');
        if ($familyId) {
            $family = Family::findOrFail($familyId);
            $this->testExistingFamily($family);
            return 0;
        }

        // تست ۱: ایجاد خانواده جدید با اعضای کامل
        $this->info('=== تست ۱: خانواده با اعضای کامل ===');
        $family = $this->createTestFamily(3, true);
        $this->testFamily($family);

        // تست ۲: ایجاد خانواده با اعضای ناقص
        $this->info('=== تست ۲: خانواده با اعضای ناقص (یک عضو ناقص) ===');
        $family = $this->createTestFamilyWithIncompleteMembers(3, 1);
        $this->testFamily($family);

        // تست ۳: ایجاد خانواده با تمام اعضای ناقص
        $this->info('=== تست ۳: خانواده با تمام اعضای ناقص ===');
        $family = $this->createTestFamily(3, false);
        $this->testFamily($family);
        
        $this->info('تست با موفقیت انجام شد!');
        
        return 0;
    }

    /**
     * تست یک خانواده موجود
     *
     * @param  \App\Models\Family  $family
     * @return void
     */
    private function testExistingFamily(Family $family)
    {
        $this->info("=== تست خانواده موجود با شناسه {$family->id} ===");
        $this->testFamily($family);
    }

    /**
     * تست منطق اعتبارسنجی برای یک خانواده
     *
     * @param  \App\Models\Family  $family
     * @return void
     */
    private function testFamily(Family $family)
    {
        $validationStatus = $family->getIdentityValidationStatus();
        
        $this->info('تعداد اعضا: ' . $family->members->count());
        $this->info('تعداد اعضای کامل: ' . $validationStatus['complete_members']);
        $this->info('وضعیت: ' . $validationStatus['status']);
        $this->info('درصد تکمیل: ' . $validationStatus['percentage'] . '%');
        $this->info('پیام: ' . $validationStatus['message']);

        // بررسی همخوانی وضعیت و درصد
        $this->checkStatusPercentageConsistency($validationStatus);
        
        // بررسی جزئیات اعضا
        $this->info('اطلاعات اعضا:');
        foreach ($family->members as $member) {
            $this->info("- {$member->first_name} {$member->last_name} ({$member->national_code}): " . 
                ($this->isMemberComplete($member) ? 'کامل' : 'ناقص'));
        }
        
        $this->info('');
    }

    /**
     * بررسی همخوانی وضعیت و درصد
     *
     * @param  array  $validationStatus
     * @return void
     */
    private function checkStatusPercentageConsistency(array $validationStatus)
    {
        $status = $validationStatus['status'];
        $percentage = $validationStatus['percentage'];
        $isConsistent = true;
        
        if ($status === 'complete' && $percentage !== 100) {
            $isConsistent = false;
            $this->error('‼️ عدم همخوانی: وضعیت کامل ولی درصد ' . $percentage);
        }
        
        if ($status === 'none' && $percentage !== 0) {
            $isConsistent = false;
            $this->error('‼️ عدم همخوانی: وضعیت ناقص ولی درصد ' . $percentage);
        }
        
        if ($validationStatus['complete_members'] === $validationStatus['total_members'] && $status !== 'complete') {
            $isConsistent = false;
            $this->error('‼️ عدم همخوانی: همه اعضا کامل ولی وضعیت ' . $status);
        }
        
        if ($isConsistent) {
            $this->info('✅ وضعیت و درصد همخوانی دارند.');
        }
    }

    /**
     * بررسی کامل بودن اطلاعات عضو
     *
     * @param  \App\Models\Member  $member
     * @return bool
     */
    private function isMemberComplete(Member $member): bool
    {
        $requiredFields = config('ui.family_validation_icons.identity.required_fields', [
            'first_name', 'last_name', 'national_code'
        ]);
        
        foreach ($requiredFields as $field) {
            if (empty($member->{$field})) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * ایجاد خانواده تستی با تعداد مشخص اعضا
     *
     * @param  int  $membersCount تعداد اعضا
     * @param  bool  $isComplete آیا اعضا کامل باشند؟
     * @return \App\Models\Family
     */
    private function createTestFamily(int $membersCount, bool $isComplete): Family
    {
        // استفاده از Factory برای ایجاد خانواده
        $family = Family::factory()->create();

        for ($i = 0; $i < $membersCount; $i++) {
            $this->createMember($family, $i === 0, $isComplete);
        }

        return $family->fresh(['members']);
    }

    /**
     * ایجاد خانواده تستی با ترکیب اعضای کامل و ناقص
     *
     * @param  int  $totalMembers تعداد کل اعضا
     * @param  int  $incompleteMembers تعداد اعضای ناقص
     * @return \App\Models\Family
     */
    private function createTestFamilyWithIncompleteMembers(int $totalMembers, int $incompleteMembers): Family
    {
        // استفاده از Factory برای ایجاد خانواده
        $family = Family::factory()->create();

        $completeMembers = $totalMembers - $incompleteMembers;
        
        // ایجاد اعضای کامل
        for ($i = 0; $i < $completeMembers; $i++) {
            $this->createMember($family, $i === 0, true);
        }
        
        // ایجاد اعضای ناقص
        for ($i = 0; $i < $incompleteMembers; $i++) {
            $this->createMember($family, false, false);
        }

        return $family->fresh(['members']);
    }

    /**
     * ایجاد یک عضو تستی برای خانواده
     *
     * @param  \App\Models\Family  $family
     * @param  bool  $isHead آیا سرپرست است؟
     * @param  bool  $isComplete آیا اطلاعات کامل باشد؟
     * @return \App\Models\Member
     */
    private function createMember(Family $family, bool $isHead, bool $isComplete): Member
    {
        $rand = rand(1000, 9999);
        
        // استفاده از Factory همراه با تنظیم مشخصات خاص
        $data = [
            'family_id' => $family->id,
            'is_head' => $isHead,
            'relationship' => $isHead ? 'head' : 'child',
            'gender' => $isHead ? 'male' : (rand(0, 1) ? 'male' : 'female'),
        ];
        
        // اگر اطلاعات کامل نیست، فیلدهای مورد نیاز را خالی می‌کنیم
        if (!$isComplete) {
            $data['last_name'] = '';
            $data['national_code'] = '';
        } else {
            $data['first_name'] = "نام-{$rand}";
            $data['last_name'] = "خانوادگی-{$rand}";
            $data['national_code'] = "1234{$rand}";
        }
        
        return Member::factory()->create($data);
    }
}
