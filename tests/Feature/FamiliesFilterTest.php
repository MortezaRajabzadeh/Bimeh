<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Family;
use App\Models\Province;
use App\Models\City;
use App\Models\Organization;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use App\Livewire\Insurance\FamiliesApproval;
use Illuminate\Support\Facades\Log;

/**
 * تست واحد برای بررسی عملکرد فیلترهای خانواده‌ها
 * 
 * @group filters
 * @group families
 */
class FamiliesFilterTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $province;
    protected $city;
    protected $charity;

    /**
     * راه‌اندازی اولیه برای تست‌ها
     */
    protected function setUp(): void
    {
        parent::setUp();

        // ایجاد کاربر تست
        $this->user = User::factory()->create([
            'organization_id' => null, // کاربر سیستم
            'role' => 'admin'
        ]);

        // ایجاد داده‌های تست
        $this->province = Province::factory()->create(['name' => 'تهران']);
        $this->city = City::factory()->create([
            'name' => 'تهران',
            'province_id' => $this->province->id
        ]);
        $this->charity = Organization::factory()->create([
            'name' => 'خیریه تست',
            'type' => 'charity'
        ]);

        // احراز هویت کاربر
        $this->actingAs($this->user);
    }

    /**
     * تست فیلتر استان
     * 
     * @test
     */
    public function test_province_filter_works_correctly()
    {
        // ایجاد خانواده‌های تست
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $otherProvince = Province::factory()->create(['name' => 'اصفهان']);
        $family2 = Family::factory()->create([
            'province_id' => $otherProvince->id,
            'charity_id' => $this->charity->id
        ]);

        // تست کامپوننت Livewire
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'province',
                    'value' => $this->province->id,
                    'operator' => 'equals',
                    'logical_operator' => 'and'
                ]
            ]);

        // بررسی که فقط خانواده متعلق به استان انتخابی نمایش داده شود
        $families = $component->get('families');
        
        $this->assertTrue($families->contains('id', $family1->id));
        $this->assertFalse($families->contains('id', $family2->id));
    }

    /**
     * تست فیلتر تعداد اعضا
     * 
     * @test
     */
    public function test_members_count_filter_works_correctly()
    {
        // ایجاد خانواده با تعداد اعضای مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->count(3)->create(['family_id' => $family1->id]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->count(5)->create(['family_id' => $family2->id]);

        // تست فیلتر تعداد اعضا برابر با 3
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'members_count',
                    'value' => 3,
                    'operator' => 'equals',
                    'logical_operator' => 'and'
                ]
            ]);

        $families = $component->get('families');
        
        $this->assertTrue($families->contains('id', $family1->id));
        $this->assertFalse($families->contains('id', $family2->id));
    }

    /**
     * تست فیلتر ترکیبی (AND)
     * 
     * @test
     */
    public function test_multiple_and_filters_work_correctly()
    {
        // ایجاد خانواده‌های تست
        $targetFamily = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->count(2)->create(['family_id' => $targetFamily->id]);

        $otherFamily = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->count(4)->create(['family_id' => $otherFamily->id]);

        // تست فیلترهای ترکیبی
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'province',
                    'value' => $this->province->id,
                    'operator' => 'equals',
                    'logical_operator' => 'and'
                ],
                [
                    'type' => 'members_count',
                    'value' => 2,
                    'operator' => 'equals',
                    'logical_operator' => 'and'
                ]
            ]);

        $families = $component->get('families');
        
        $this->assertTrue($families->contains('id', $targetFamily->id));
        $this->assertFalse($families->contains('id', $otherFamily->id));
    }

    /**
     * تست فیلتر OR
     * 
     * @test
     */
    public function test_or_filters_work_correctly()
    {
        // ایجاد خانواده‌های تست
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->count(2)->create(['family_id' => $family1->id]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->count(5)->create(['family_id' => $family2->id]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->count(3)->create(['family_id' => $family3->id]);

        // تست فیلتر OR برای تعداد اعضا
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'members_count',
                    'value' => 2,
                    'operator' => 'equals',
                    'logical_operator' => 'or'
                ],
                [
                    'type' => 'members_count',
                    'value' => 5,
                    'operator' => 'equals',
                    'logical_operator' => 'or'
                ]
            ]);

        $families = $component->get('families');
        
        $this->assertTrue($families->contains('id', $family1->id));
        $this->assertTrue($families->contains('id', $family2->id));
        $this->assertFalse($families->contains('id', $family3->id));
    }

    /**
     * تست مدیریت خطا در فیلترها
     * 
     * @test
     */
    public function test_filter_error_handling()
    {
        // ایجاد فیلتر با داده‌های نامعتبر
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'invalid_filter_type',
                    'value' => 'invalid_value',
                    'operator' => 'equals',
                    'logical_operator' => 'and'
                ]
            ]);

        // بررسی که کامپوننت خطا نمی‌دهد و به‌درستی کار می‌کند
        $this->assertNotNull($component->get('families'));
        
        // بررسی لاگ خطا
        Log::shouldReceive('warning')
            ->once()
            ->with('⚠️ Unknown filter type', \Mockery::type('array'));
    }

    /**
     * تست کارایی فیلترها با داده‌های زیاد
     * 
     * @test
     */
    public function test_filter_performance_with_large_dataset()
    {
        // ایجاد تعداد زیادی خانواده
        Family::factory()->count(100)->create([
            'province_id' => $this->province->id,
            'charity_id' => $this->charity->id
        ]);

        $startTime = microtime(true);

        // اعمال فیلتر
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'province',
                    'value' => $this->province->id,
                    'operator' => 'equals',
                    'logical_operator' => 'and'
                ]
            ]);

        $families = $component->get('families');
        $endTime = microtime(true);

        // بررسی که زمان اجرا قابل قبول باشد (کمتر از 2 ثانیه)
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(2.0, $executionTime, 'Filter execution took too long: ' . $executionTime . ' seconds');
        
        // بررسی که همه خانواده‌ها فیلتر شده‌اند
        $this->assertCount(100, $families);
    }

    /**
     * تست فیلتر معیارهای پذیرش
     * 
     * @test
     */
    public function test_acceptance_criteria_filter()
    {
        // ایجاد خانواده با معیار خاص
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'charity_id' => $this->charity->id,
            'acceptance_criteria' => ['سرپرست خانوار زن', 'خانواده کم برخوردار']
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'charity_id' => $this->charity->id,
            'acceptance_criteria' => ['خانواده کم برخوردار']
        ]);

        // تست فیلتر معیار پذیرش
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'acceptance_criteria',
                    'value' => 'سرپرست خانوار زن',
                    'operator' => 'equals',
                    'logical_operator' => 'and'
                ]
            ]);

        $families = $component->get('families');
        
        $this->assertTrue($families->contains('id', $family1->id));
        $this->assertFalse($families->contains('id', $family2->id));
    }

    /**
     * تست پاک‌سازی کش هنگام اعمال فیلترها
     * 
     * @test
     */
    public function test_cache_clearing_on_filter_application()
    {
        $family = Family::factory()->create([
            'province_id' => $this->province->id,
            'charity_id' => $this->charity->id
        ]);

        $component = Livewire::test(FamiliesApproval::class);
        
        // بارگذاری اولیه (ایجاد کش)
        $initialFamilies = $component->get('families');
        
        // اعمال فیلتر جدید
        $component->set('tempFilters', [
            [
                'type' => 'province',
                'value' => $this->province->id,
                'operator' => 'equals',
                'logical_operator' => 'and'
            ]
        ]);

        $filteredFamilies = $component->get('families');
        
        // بررسی که نتایج به‌روزرسانی شده‌اند
        $this->assertNotEquals($initialFamilies, $filteredFamilies);
        $this->assertTrue($filteredFamilies->contains('id', $family->id));
    }
}
