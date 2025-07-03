<?php

namespace Tests\Unit;

use App\Models\Family;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * تست حالتی که همه اعضا کامل هستند.
     * در این حالت باید وضعیت 'complete' و درصد 100 باشد.
     */
    public function test_all_members_complete_validation_status()
    {
        // ساخت خانواده با 3 عضو که همه فیلدهای الزامی را دارند
        $family = Family::factory()->create();

        // ساخت 3 عضو با اطلاعات کامل
        for ($i = 0; $i < 3; $i++) {
            Member::factory()->create([
                'family_id' => $family->id,
                'first_name' => 'نام' . $i,
                'last_name' => 'خانوادگی' . $i,
                'national_code' => '123456789' . $i,
                'is_head' => $i === 0, // اولین عضو سرپرست است
            ]);
        }

        // بررسی وضعیت اعتبارسنجی
        $status = $family->getIdentityValidationStatus();

        // انتظار داریم وضعیت 'complete' و درصد 100 باشد
        $this->assertEquals('complete', $status['status']);
        $this->assertEquals(100, $status['percentage']);
        $this->assertEquals(3, $status['complete_members']);
        $this->assertEquals(3, $status['total_members']);
        
        // بررسی جزئیات لاگ
        \Illuminate\Support\Facades\Log::shouldReceive('debug')
            ->with('ValidationStatus', \Mockery::on(function($args) {
                return $args['status'] === 'complete' && $args['adjusted_percentage'] === 100;
            }));
    }

    /**
     * تست حالتی که بعضی از اعضا ناقص هستند.
     * در این حالت باید وضعیت 'partial' و درصد متناسب باشد.
     */
    public function test_partial_members_validation_status()
    {
        // ساخت خانواده
        $family = Family::factory()->create();

        // ساخت 2 عضو کامل
        for ($i = 0; $i < 2; $i++) {
            Member::factory()->create([
                'family_id' => $family->id,
                'first_name' => 'نام' . $i,
                'last_name' => 'خانوادگی' . $i,
                'national_code' => '123456789' . $i,
            ]);
        }

        // ساخت 1 عضو ناقص (بدون نام خانوادگی و کد ملی)
        Member::factory()->create([
            'family_id' => $family->id,
            'first_name' => 'نام ناقص',
            'last_name' => '',
            'national_code' => '',
        ]);

        // بررسی وضعیت اعتبارسنجی
        $status = $family->getIdentityValidationStatus();

        // انتظار داریم وضعیت 'partial' باشد (چون 2 از 3 عضو کامل هستند)
        $this->assertEquals('partial', $status['status']);
        $this->assertTrue($status['percentage'] > 0 && $status['percentage'] < 100);
        $this->assertEquals(2, $status['complete_members']);
        $this->assertEquals(3, $status['total_members']);
    }

    /**
     * تست حالتی که هیچ عضوی کامل نیست.
     * در این حالت باید وضعیت 'none' و درصد 0 باشد.
     */
    public function test_no_complete_members_validation_status()
    {
        // ساخت خانواده
        $family = Family::factory()->create();

        // ساخت 3 عضو ناقص
        for ($i = 0; $i < 3; $i++) {
            Member::factory()->create([
                'family_id' => $family->id,
                'first_name' => 'نام' . $i,
                'last_name' => '',  // فیلد الزامی خالی
                'national_code' => '', // فیلد الزامی خالی
            ]);
        }

        // بررسی وضعیت اعتبارسنجی
        $status = $family->getIdentityValidationStatus();

        // انتظار داریم وضعیت 'none' و درصد 0 باشد
        $this->assertEquals('none', $status['status']);
        $this->assertEquals(0, $status['percentage']);
        $this->assertEquals(0, $status['complete_members']);
        $this->assertEquals(3, $status['total_members']);
    }
}
