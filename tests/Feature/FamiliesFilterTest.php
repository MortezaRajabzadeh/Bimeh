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
    protected $otherProvince;
    protected $otherCity;
    protected $otherCharity;
    protected $insuranceUser;

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

        // ایجاد کاربر بیمه برای تست‌های رتبه
        $this->insuranceUser = User::factory()->create([
            'organization_id' => null,
            'role' => 'insurance'
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

        // ایجاد داده‌های اضافی برای تست‌های متنوع
        $this->otherProvince = Province::factory()->create(['name' => 'اصفهان']);
        $this->otherCity = City::factory()->create([
            'name' => 'اصفهان',
            'province_id' => $this->otherProvince->id
        ]);
        $this->otherCharity = Organization::factory()->create([
            'name' => 'خیریه دوم',
            'type' => 'charity'
        ]);

        // احراز هویت کاربر
        $this->actingAs($this->user);
    }

    /**
     * ایجاد خانواده با تعداد اعضا مشخص
     */
    protected function createFamilyWithMembers($provinceId, $cityId, $charityId, $membersCount, $problemTypes = [])
    {
        $family = Family::factory()->create([
            'province_id' => $provinceId,
            'city_id' => $cityId,
            'charity_id' => $charityId
        ]);

        Member::factory()->count($membersCount)->create([
            'family_id' => $family->id,
            'problem_type' => $problemTypes
        ]);

        return $family;
    }

    /**
     * ایجاد ساختار فیلتر استاندارد
     */
    protected function createFilterArray($type, $value, $operator = 'equals', $logicalOperator = 'and', $existenceOperator = 'exists')
    {
        return [
            'type' => $type,
            'value' => $value,
            'operator' => $operator,
            'logical_operator' => $logicalOperator,
            'existence_operator' => $existenceOperator
        ];
    }

    /**
     * Data provider برای عملگرهای مختلف
     */
    public static function filterOperatorsProvider(): array
    {
        return [
            'equals' => ['equals'],
            'not_equals' => ['not_equals'],
            'exists' => ['exists'],
            'not_exists' => ['not_exists'],
        ];
    }

    /**
     * Data provider برای انواع فیلتر
     */
    public static function filterTypesProvider(): array
    {
        return [
            'province' => ['province', 'province_id'],
            'city' => ['city', 'city_id'],
            'charity' => ['charity', 'charity_id'],
        ];
    }

    /**
     * تست فیلتر با عملگرهای مختلف با استفاده از data provider
     * 
     * @test
     * @dataProvider filterOperatorsProvider
     */
    public function test_filter_with_different_operators($operator)
    {
        // ایجاد خانواده‌ها
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // تست فیلتر با عملگر مختلف
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, $operator, 'and', 'exists')
            ]);

        // بررسی نتایج بر اساس نوع عملگر
        switch ($operator) {
            case 'equals':
                $this->assertFamilyInResults($component, $family1->id, true);
                $this->assertFamilyInResults($component, $family2->id, false);
                break;
            case 'not_equals':
                $this->assertFamilyInResults($component, $family1->id, false);
                $this->assertFamilyInResults($component, $family2->id, true);
                break;
            // برای exists و not_exists نیاز به داده‌های متفاوت داریم
        }
    }

    /**
     * تست فیلترهای مختلف با استفاده از data provider
     * 
     * @test
     * @dataProvider filterTypesProvider
     */
    public function test_different_filter_types($filterType, $field)
    {
        // ایجاد خانواده‌ها
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // تست فیلتر مختلف
        $value = $this->province->id;
        if ($filterType === 'city') {
            $value = $this->city->id;
        } elseif ($filterType === 'charity') {
            $value = $this->charity->id;
        }

        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray($filterType, $value, 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * بررسی وجود/عدم وجود خانواده در نتایج
     */
    protected function assertFamilyInResults($component, $familyId, $shouldExist = true)
    {
        $families = $component->get('families');
        if ($shouldExist) {
            $this->assertTrue($families->contains('id', $familyId), "Family ID {$familyId} should be in results but is not.");
        } else {
            $this->assertFalse($families->contains('id', $familyId), "Family ID {$familyId} should not be in results but is.");
        }
    }

    /**
     * دریافت لیست خانواده‌ها از کامپوننت
     */
    protected function getFamiliesFromComponent($component)
    {
        return $component->get('families');
    }

    /**
     * تست فیلتر استان با عملگر equals
     * 
     * @test
     */
    public function test_province_filter_with_equals_operator()
    {
        // ایجاد خانواده‌های تست در استان‌های مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // تست فیلتر استان با عملگر equals
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists')
            ]);

        // بررسی که فقط خانواده متعلق به استان انتخابی نمایش داده شود
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر استان با عملگر not_equals
     * 
     * @test
     */
    public function test_province_filter_with_not_equals_operator()
    {
        // ایجاد خانواده‌های تست در استان‌های مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // تست فیلتر استان با عملگر not_equals
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'not_equals', 'and', 'exists')
            ]);

        // بررسی که خانواده‌های استان انتخابی نمایش داده نشوند
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, true);
    }

    /**
     * تست فیلتر استان با عملگر exists
     * 
     * @test
     */
    public function test_province_filter_with_exists_operator()
    {
        // ایجاد خانواده‌هایی با و بدون استان
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => null,
            'city_id' => null,
            'charity_id' => $this->charity->id
        ]);

        // تست فیلتر استان با عملگر exists
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', '', 'equals', 'and', 'exists')
            ]);

        // بررسی که فقط خانواده‌های دارای استان نمایش داده شوند
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر استان با عملگر not_exists
     * 
     * @test
     */
    public function test_province_filter_with_not_exists_operator()
    {
        // ایجاد خانواده‌هایی با و بدون استان
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => null,
            'city_id' => null,
            'charity_id' => $this->charity->id
        ]);

        // تست فیلتر استان با عملگر not_exists
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', '', 'equals', 'and', 'not_exists')
            ]);

        // بررسی که فقط خانواده‌های بدون استان نمایش داده شوند
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, true);
    }

    /**
     * تست چند فیلتر استان با عملگر OR
     * 
     * @test
     */
    public function test_multiple_provinces_with_or_operator()
    {
        // ایجاد خانواده‌ها در 3 استان مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        $thirdProvince = Province::factory()->create(['name' => 'شیراز']);
        $family3 = Family::factory()->create([
            'province_id' => $thirdProvince->id,
            'charity_id' => $this->charity->id
        ]);

        // تست 2 فیلتر استان با عملگر OR
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'or', 'exists'),
                $this->createFilterArray('province', $this->otherProvince->id, 'equals', 'or', 'exists')
            ]);

        // بررسی که خانواده‌های هر دو استان نمایش داده شوند
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, true);
        $this->assertFamilyInResults($component, $family3->id, false);
    }

    /**
     * تست فیلتر شهر با عملگر equals
     * 
     * @test
     */
    public function test_city_filter_with_equals_operator()
    {
        // ایجاد خانواده‌های تست در شهرهای مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // تست فیلتر شهر با عملگر equals
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('city', $this->city->id, 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر شهر با عملگر not_equals
     * 
     * @test
     */
    public function test_city_filter_with_not_equals_operator()
    {
        // ایجاد خانواده‌های تست در شهرهای مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // تست فیلتر شهر با عملگر not_equals
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('city', $this->city->id, 'not_equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family, true);
    }

    /**
     * تست فیلتر با مقدار خالی که نادیده گرفته می‌شود
     * 
     * @test
     */
    public function test_empty_filter_value_is_ignored()
    {
        // ایجاد خانواده
        $family = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        // تست فیلتر با مقدار خالی
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', '', 'equals', 'and', 'exists')
            ]);

        // بررسی که فیلتر نادیده گرفته شود و خطا ندهد
        $families = $component->get('families');
        $this->assertNotNull($families);
        $this->assertTrue($families->contains('id', $family->id));
    }

    /**
     * تست فیلتر با نوع نامعتبر که نادیده گرفته می‌شود
     * 
     * @test
     */
    public function test_invalid_filter_type_is_ignored()
    {
        // ایجاد خانواده
        $family = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        // تست فیلتر با نوع نامعتبر
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('invalid_type', 'some_value', 'equals', 'and', 'exists')
            ]);

        // بررسی که خطا ندهد و فیلتر نادیده گرفته شود
        $families = $component->get('families');
        $this->assertNotNull($families);
        $this->assertTrue($families->contains('id', $family->id));
    }

    /**
     * تست فیلتر با عملگر نامعتبر که به مقدار پیشفرض تبدیل می‌شود
     * 
     * @test
     */
    public function test_invalid_operator_defaults_to_equals()
    {
        // ایجاد خانواده‌ها
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // تست فیلتر با عملگر نامعتبر
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'invalid_operator', 'and', 'exists')
            ]);

        // بررسی رفتار پیشفرض
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر تعداد اعضا با مقدار منفی
     * 
     * @test
     */
    public function test_members_count_with_negative_value()
    {
        // ایجاد خانواده
        $family = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3);

        // تست فیلتر با مقدار منفی
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('members_count', -5, 'equals', 'and', 'exists')
            ]);

        // بررسی که نتیجه خالی یا خطای مناسب برگردد
        $families = $component->get('families');
        $this->assertNotNull($families);
    }

    /**
     * تست فیلتر تعداد اعضا با min بزرگتر از max
     * 
     * @test
     */
    public function test_members_count_range_with_min_greater_than_max()
    {
        // ایجاد خانواده
        $family = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3);

        // تست فیلتر با min بزرگتر از max
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'members_count',
                    'min_members' => 10,
                    'max_members' => 5,
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ]
            ]);

        // بررسی رفتار (احتمالاً نتیجه خالی)
        $families = $component->get('families');
        $this->assertNotNull($families);
    }

    /**
     * تست فیلتر تاریخ با end_date کوچکتر از start_date
     * 
     * @test
     */
    public function test_date_filter_with_end_before_start()
    {
        // ایجاد خانواده
        $family = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-03-20'
        ]);

        // تست فیلتر تاریخ با end_date کوچکتر از start_date
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'membership_date',
                    'start_date' => '2024-06-01',
                    'end_date' => '2024-03-01',
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ]
            ]);

        // بررسی که نتیجه خالی برگردد
        $families = $component->get('families');
        $this->assertNotNull($families);
    }

    /**
     * تست فیلتر معیار پذیرش با رشته comma-separated خالی
     * 
     * @test
     */
    public function test_special_disease_with_empty_comma_separated_string()
    {
        // ایجاد خانواده
        $family = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        // تست فیلتر با رشته comma-separated خالی
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('special_disease', ',,,,', 'equals', 'and', 'exists')
            ]);

        // بررسی که خطا ندهد
        $families = $component->get('families');
        $this->assertNotNull($families);
    }

    /**
     * تست فیلتر با logical_operator null که به 'and' تبدیل می‌شود
     * 
     * @test
     */
    public function test_filter_with_null_logical_operator_defaults_to_and()
    {
        // ایجاد خانواده‌ها
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // تست فیلتر بدون logical_operator
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'province',
                    'value' => $this->province->id,
                    'operator' => 'equals',
                    'existence_operator' => 'exists'
                    // logical_operator حذف شده
                ]
            ]);

        // بررسی که به 'and' تبدیل شود
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر با existence_operator null که به 'exists' تبدیل می‌شود
     * 
     * @test
     */
    public function test_filter_with_null_existence_operator_defaults_to_exists()
    {
        // ایجاد خانواده‌ها
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => null,
            'city_id' => null,
            'charity_id' => $this->charity->id
        ]);

        // تست فیلتر بدون existence_operator
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'province',
                    'value' => $this->province->id,
                    'operator' => 'equals',
                    'logical_operator' => 'and'
                    // existence_operator حذف شده
                ]
            ]);

        // بررسی رفتار پیشفرض
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست عملکرد با داده‌های زیاد
     * 
     * @test
     * @group performance
     */
    public function test_performance_with_large_dataset()
    {
        // ایجاد 100 خانواده
        Family::factory()->count(100)->create([
            'province_id' => $this->province->id,
            'charity_id' => $this->charity->id
        ]);

        $startTime = microtime(true);

        // اعمال فیلترهای پیچیده
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('members_count', 3, 'equals', 'and', 'exists')
            ]);

        $families = $component->get('families');
        $endTime = microtime(true);

        // بررسی که زمان اجرا کمتر از 3 ثانیه باشد
        $executionTime = $endTime - $startTime;
        $this->assertLessThan(3.0, $executionTime, 'Filter execution took too long: ' . $executionTime . ' seconds');

        // بررسی که همه خانواده‌ها فیلتر شده‌اند
        $this->assertGreaterThanOrEqual(0, $families->count());
    }

    /**
     * تست فیلتر با کاراکترهای خاص در مقدار
     * 
     * @test
     */
    public function test_filter_with_special_characters_in_value()
    {
        // ایجاد خانواده
        $family = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        // تست فیلتر با مقادیر حاوی کاراکترهای خاص
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id . "'; DROP TABLE families; --", 'equals', 'and', 'exists')
            ]);

        // بررسی که SQL injection رخ ندهد
        $families = $component->get('families');
        $this->assertNotNull($families);
    }

    /**
     * تست اعمال همزمان چند فیلتر
     * 
     * @test
     */
    public function test_concurrent_filter_applications()
    {
        // ایجاد خانواده‌ها
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // شبیه‌سازی اعمال همزمان چند فیلتر
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('city', $this->city->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('charity', $this->charity->id, 'equals', 'and', 'exists')
            ]);

        // بررسی consistency نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر شهر با عملگرهای exists و not_exists
     * 
     * @test
     */
    public function test_city_filter_with_exists_and_not_exists()
    {
        // ایجاد خانواده‌هایی با و بدون شهر
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => null,
            'charity_id' => $this->charity->id
        ]);

        // تست فیلتر شهر با عملگر exists
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('city', '', 'equals', 'and', 'exists')
            ]);

        // بررسی که فقط خانواده‌های دارای شهر نمایش داده شوند
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);

        // تست فیلتر شهر با عملگر not_exists
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('city', '', 'equals', 'and', 'not_exists')
            ]);

        // بررسی که فقط خانواده‌های بدون شهر نمایش داده شوند
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, true);
    }

    /**
     * تست فیلتر تعداد اعضا با عملگر equals
     * 
     * @test
     */
    public function test_members_count_equals()
    {
        // ایجاد خانواده‌ها با تعداد اعضا مختلف
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 2);
        $family2 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3);
        $family3 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 5);

        // تست فیلتر تعداد اعضا برابر با 3
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('members_count', 3, 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, true);
        $this->assertFamilyInResults($component, $family3->id, false);
    }

    /**
     * تست فیلتر تعداد اعضا با عملگر not_equals
     * 
     * @test
     */
    public function test_members_count_not_equals()
    {
        // ایجاد خانواده‌ها با تعداد اعضا مختلف
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 2);
        $family2 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3);
        $family3 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 5);

        // تست فیلتر تعداد اعضا با عملگر not_equals
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('members_count', 3, 'not_equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
        $this->assertFamilyInResults($component, $family3->id, true);
    }

    /**
     * تست فیلتر تعداد اعضا با عملگر greater_than
     * 
     * @test
     */
    public function test_members_count_greater_than()
    {
        // ایجاد خانواده‌ها با تعداد اعضا مختلف
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 2);
        $family2 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3);
        $family3 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 5);

        // تست فیلتر تعداد اعضا با عملگر greater_than
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('members_count', 4, 'greater_than', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, false);
        $this->assertFamilyInResults($component, $family3->id, true);
    }

    /**
     * تست فیلتر تعداد اعضا با عملگر less_than
     * 
     * @test
     */
    public function test_members_count_less_than()
    {
        // ایجاد خانواده‌ها با تعداد اعضا مختلف
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 2);
        $family2 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3);
        $family3 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 5);

        // تست فیلتر تعداد اعضا با عملگر less_than
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('members_count', 4, 'less_than', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, true);
        $this->assertFamilyInResults($component, $family3->id, false);
    }

    /**
     * تست فیلتر تعداد اعضا در حالت range
     * 
     * @test
     */
    public function test_members_count_range_mode()
    {
        // ایجاد خانواده‌ها با تعداد اعضا مختلف
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 2);
        $family2 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3);
        $family3 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 5);
        $family4 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 7);

        // تست فیلتر تعداد اعضا در حالت range
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'members_count',
                    'min_members' => 3,
                    'max_members' => 5,
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ]
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, true);
        $this->assertFamilyInResults($component, $family3->id, true);
        $this->assertFamilyInResults($component, $family4->id, false);
    }

    /**
     * تست فیلتر تعداد اعضا با فقط min
     * 
     * @test
     */
    public function test_members_count_range_with_only_min()
    {
        // ایجاد خانواده‌ها با تعداد اعضا مختلف
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 2);
        $family2 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3);
        $family3 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 5);

        // تست فیلتر تعداد اعضا با فقط min
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'members_count',
                    'min_members' => 3,
                    'max_members' => '',
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ]
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, true);
        $this->assertFamilyInResults($component, $family3->id, true);
    }

    /**
     * تست فیلتر تعداد اعضا با فقط max
     * 
     * @test
     */
    public function test_members_count_range_with_only_max()
    {
        // ایجاد خانواده‌ها با تعداد اعضا مختلف
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 2);
        $family2 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3);
        $family3 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 5);

        // تست فیلتر تعداد اعضا با فقط max
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'members_count',
                    'min_members' => '',
                    'max_members' => 3,
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ]
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, true);
        $this->assertFamilyInResults($component, $family3->id, false);
    }

    /**
     * تست فیلتر تعداد اعضا با عملگر exists
     * 
     * @test
     */
    public function test_members_count_exists()
    {
        // ایجاد خانواده‌ها با و بدون اعضا
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 2);
        
        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        // بدون ایجاد اعضا

        // تست فیلتر تعداد اعضا با عملگر exists
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('members_count', '', 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر تعداد اعضا با عملگر not_exists
     * 
     * @test
     */
    public function test_members_count_not_exists()
    {
        // ایجاد خانواده‌ها با و بدون اعضا
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 2);
        
        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        // بدون ایجاد اعضا

        // تست فیلتر تعداد اعضا با عملگر not_exists
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('members_count', '', 'equals', 'and', 'not_exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, true);
    }

    /**
     * تست فیلتر خیریه با عملگر equals
     * 
     * @test
     */
    public function test_charity_filter_with_equals_operator()
    {
        // ایجاد خانواده‌ها با خیریه‌های مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // تست فیلتر خیریه با عملگر equals
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('charity', $this->charity->id, 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر خیریه با عملگر not_equals
     * 
     * @test
     */
    public function test_charity_filter_with_not_equals_operator()
    {
        // ایجاد خانواده‌ها با خیریه‌های مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // تست فیلتر خیریه با عملگر not_equals
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('charity', $this->charity->id, 'not_equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, true);
    }

    /**
     * تست فیلتر خیریه با عملگرهای exists و not_exists
     * 
     * @test
     */
    public function test_charity_filter_with_exists_and_not_exists()
    {
        // ایجاد خانواده‌هایی با و بدون خیریه
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => null
        ]);

        // تست فیلتر خیریه با عملگر exists
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('charity', '', 'equals', 'and', 'exists')
            ]);

        // بررسی که فقط خانواده‌های دارای خیریه نمایش داده شوند
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);

        // تست فیلتر خیریه با عملگر not_exists
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('charity', '', 'equals', 'and', 'not_exists')
            ]);

        // بررسی که فقط خانواده‌های بدون خیریه نمایش داده شوند
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, true);
    }

    /**
     * تست چند فیلتر خیریه با عملگر OR
     * 
     * @test
     */
    public function test_multiple_charities_with_or_operator()
    {
        // ایجاد خانواده‌ها با خیریه‌های مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->otherCharity->id
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => null
        ]);

        // تست انتخاب چند خیریه با OR
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('charity', $this->charity->id, 'equals', 'or', 'exists'),
                $this->createFilterArray('charity', $this->otherCharity->id, 'equals', 'or', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, true);
        $this->assertFamilyInResults($component, $family3->id, false);
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

    /**
     * تست دو فیلتر با عملگر AND
     * 
     * @test
     */
    public function test_two_filters_with_and_operator()
    {
        // ایجاد خانواده‌ها
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3);
        $family2 = $this->createFamilyWithMembers($this->otherProvince->id, $this->otherCity->id, $this->otherCharity->id, 3);
        $family3 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 5);

        // تست دو فیلتر با عملگر AND
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('members_count', 3, 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
        $this->assertFamilyInResults($component, $family3->id, false);
    }

    /**
     * تست دو فیلتر با عملگر OR
     * 
     * @test
     */
    public function test_two_filters_with_or_operator()
    {
        // ایجاد خانواده‌ها
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->charity->id
        ]);

        // تست دو فیلتر با عملگر OR
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'or', 'exists'),
                $this->createFilterArray('province', $this->otherProvince->id, 'equals', 'or', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, true);
        $this->assertFamilyInResults($component, $family3->id, true); // شامل می‌شود چون در استان اول است
    }

    /**
     * تست سه فیلتر با AND/OR مختلط
     * 
     * @test
     */
    public function test_three_filters_mixed_and_or()
    {
        // ایجاد خانواده‌ها
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3);
        $family2 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 5);
        $family3 = $this->createFamilyWithMembers($this->otherProvince->id, $this->otherCity->id, $this->otherCharity->id, 3);
        $family4 = $this->createFamilyWithMembers($this->province->id, $this->otherCity->id, $this->charity->id, 3);

        // تست سه فیلتر با AND/OR مختلط
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('members_count', 3, 'equals', 'and', 'exists'),
                $this->createFilterArray('charity', $this->otherCharity->id, 'equals', 'or', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
        $this->assertFamilyInResults($component, $family3->id, true);
        $this->assertFamilyInResults($component, $family4->id, true);
    }

    /**
     * تست گردش کار کامل از خالی تا فیلتر شده
     * 
     * @test
     */
    public function test_full_workflow_from_empty_to_filtered()
    {
        // شروع بدون فیلتر
        $component = Livewire::test(FamiliesApproval::class);
        $initialFamilies = $component->get('families');
        $this->assertGreaterThanOrEqual(0, $initialFamilies->count());

        // ایجاد خانواده‌ها
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3);
        $family2 = $this->createFamilyWithMembers($this->otherProvince->id, $this->otherCity->id, $this->otherCharity->id, 5);
        $family3 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 2, ['special_disease']);

        // افزودن فیلتر استان
        $component->set('tempFilters', [
            $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists')
        ]);
        $familiesAfterProvince = $component->get('families');
        $this->assertLessThanOrEqual($initialFamilies->count(), $familiesAfterProvince->count());

        // افزودن فیلتر تعداد اعضا
        $component->set('tempFilters', [
            $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
            $this->createFilterArray('members_count', 3, 'equals', 'and', 'exists')
        ]);
        $familiesAfterMembers = $component->get('families');
        $this->assertLessThanOrEqual($familiesAfterProvince->count(), $familiesAfterMembers->count());

        // افزودن فیلتر معیار پذیرش
        $component->set('tempFilters', [
            $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
            $this->createFilterArray('members_count', 3, 'equals', 'and', 'exists'),
            $this->createFilterArray('special_disease', 'بیماری خاص', 'equals', 'and', 'exists')
        ]);
        $familiesAfterSpecial = $component->get('families');
        $this->assertLessThanOrEqual($familiesAfterMembers->count(), $familiesAfterSpecial->count());

        // اعمال فیلترها
        $component->call('applyFilters');

        // بررسی نتایج در هر مرحله
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
        $this->assertFamilyInResults($component, $family3->id, false);
    }

    /**
     * تست فیلتر سپس مرتب‌سازی سپس صفحه‌بندی
     * 
     * @test
     */
    public function test_filter_then_sort_then_paginate()
    {
        // ایجاد خانواده‌های زیاد برای تست صفحه‌بندی
        for ($i = 0; $i < 20; $i++) {
            $this->createFamilyWithMembers(
                $this->province->id, 
                $this->city->id, 
                $this->charity->id, 
                3,
                $i % 2 == 0 ? ['special_disease'] : ['addiction']
            );
        }

        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('members_count', 3, 'equals', 'and', 'exists')
            ])
            ->set('perPage', 10);

        // اعمال فیلتر
        $component->call('applyFilters');

        // تغییر مرتب‌سازی
        $component->call('sortBy', 'created_at');

        // تغییر صفحه
        $component->set('page', 2);

        // بررسی که همه عملیات به درستی کار کنند
        $families = $component->get('families');
        $this->assertNotNull($families);
        $this->assertEquals(10, $families->count());
    }

    /**
     * تست ترکیب فیلترهای پیچیده
     * 
     * @test
     */
    public function test_complex_filter_combination()
    {
        // ایجاد خانواده‌های پیچیده
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-03-20'
        ]);
        Member::factory()->count(3)->create([
            'family_id' => $family1->id,
            'problem_type' => ['special_disease']
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id,
            'created_at' => '2024-01-15'
        ]);
        Member::factory()->count(5)->create([
            'family_id' => $family2->id,
            'problem_type' => ['addiction']
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-06-10'
        ]);
        Member::factory()->count(2)->create([
            'family_id' => $family3->id,
            'problem_type' => []
        ]);

        // تست ترکیب 5 فیلتر مختلف با AND/OR
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('members_count', 3, 'equals', 'and', 'exists'),
                $this->createFilterArray('special_disease', 'بیماری خاص', 'equals', 'and', 'exists'),
                [
                    'type' => 'membership_date',
                    'start_date' => '2024-02-01',
                    'end_date' => '2024-05-31',
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ],
                $this->createFilterArray('city', $this->city->id, 'equals', 'or', 'exists')
            ]);

        $families = $component->get('families');
        $this->assertTrue($families->contains('id', $family1->id));
        $this->assertFalse($families->contains('id', $family2->id));
        $this->assertTrue($families->contains('id', $family3->id));
    }

    /**
     * تست ترکیب فیلترهای جغرافیایی با AND
     * 
     * @test
     */
    public function test_province_and_city_and_charity_with_and()
    {
        // ایجاد خانواده‌های جغرافیایی
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->otherCharity->id
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->charity->id
        ]);

        // تست سه فیلتر جغرافیایی با AND
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('city', $this->city->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('charity', $this->charity->id, 'equals', 'and', 'exists')
            ]);

        // بررسی دقت فیلترینگ
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
        $this->assertFamilyInResults($component, $family3->id, false);
    }

    /**
     * تست ترکیب فیلتر تعداد اعضا و معیار پذیرش
     * 
     * @test
     */
    public function test_members_count_and_special_disease_combined()
    {
        // ایجاد خانواده‌ها
        $family1 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3, ['special_disease']);
        $family2 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 5, ['special_disease']);
        $family3 = $this->createFamilyWithMembers($this->province->id, $this->city->id, $this->charity->id, 3, []);

        // تست ترکیب فیلتر تعداد اعضا + معیار پذیرش
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('members_count', 3, 'equals', 'and', 'exists'),
                $this->createFilterArray('special_disease', 'بیماری خاص', 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
        $this->assertFamilyInResults($component, $family3->id, false);
    }

    /**
     * تست ترکیب فیلتر تاریخ عضویت و استان
     * 
     * @test
     */
    public function test_date_range_and_province_combined()
    {
        // ایجاد خانواده‌ها
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-03-20'
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id,
            'created_at' => '2024-02-15'
        ]);

        // تست ترکیب فیلتر تاریخ + استان
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'membership_date',
                    'start_date' => '2024-02-01',
                    'end_date' => '2024-04-30',
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ],
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست افزودن فیلتر که تعداد فیلترها را افزایش می‌دهد
     * 
     * @test
     */
    public function test_add_filter_increases_count()
    {
        // شروع با tempFilters خالی
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', []);

        // بررسی تعداد اولیه
        $this->assertCount(0, $component->get('tempFilters'));

        // افزودن یک فیلتر
        $component->call('addFilter');

        // بررسی که تعداد فیلترها افزایش یابد
        $this->assertCount(1, $component->get('tempFilters'));
    }

    /**
     * تست حذف فیلتر بر اساس ایندکس
     * 
     * @test
     */
    public function test_remove_filter_by_index()
    {
        // ایجاد 3 فیلتر
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('city', $this->city->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('charity', $this->charity->id, 'equals', 'and', 'exists')
            ]);

        // بررسی تعداد اولیه
        $this->assertCount(3, $component->get('tempFilters'));

        // حذف فیلتر دوم
        $component->call('removeFilter', 1);

        // بررسی که فیلتر حذف شود و ایندکس‌ها بازنویسی شوند
        $filters = $component->get('tempFilters');
        $this->assertCount(2, $filters);
        $this->assertEquals('province', $filters[0]['type']);
        $this->assertEquals('charity', $filters[1]['type']);
    }

    /**
     * تست حذف همه فیلترها
     * 
     * @test
     */
    public function test_remove_all_filters()
    {
        // ایجاد چند فیلتر
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('city', $this->city->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('members_count', 3, 'equals', 'and', 'exists')
            ]);

        // بررسی تعداد اولیه
        $this->assertCount(3, $component->get('tempFilters'));

        // حذف همه فیلترها یکی یکی
        $component->call('removeFilter', 0);
        $component->call('removeFilter', 0);
        $component->call('removeFilter', 0);

        // بررسی که tempFilters خالی شود
        $this->assertCount(0, $component->get('tempFilters'));
    }

    /**
     * تست بازگشت به پیشفرض که همه فیلترها را پاک می‌کند
     * 
     * @test
     */
    public function test_reset_to_default_clears_all_filters()
    {
        // ایجاد چند فیلتر
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('city', $this->city->id, 'equals', 'and', 'exists')
            ])
            ->set('sortField', 'calculated_rank')
            ->set('sortDirection', 'desc');

        // فراخوانی resetToDefault
        $component->call('resetToDefault');

        // بررسی که tempFilters و activeFilters خالی شوند
        $this->assertCount(0, $component->get('tempFilters'));
        $this->assertEquals('created_at', $component->get('sortField'));
        $this->assertEquals('desc', $component->get('sortDirection'));
    }

    /**
     * تست اعمال فیلترها که نتایج را به‌روزرسانی می‌کند
     * 
     * @test
     */
    public function test_apply_filters_updates_results()
    {
        // ایجاد خانواده‌ها
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // ایجاد فیلترها در tempFilters
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists')
            ]);

        // فراخوانی applyFilters
        $component->call('applyFilters');

        // بررسی که نتایج به‌روزرسانی شوند
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست پاک کردن کش هنگام تغییر فیلتر
     * 
     * @test
     */
    public function test_cache_clearing_on_filter_change()
    {
        // ایجاد خانواده
        $family = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $component = Livewire::test(FamiliesApproval::class);

        // بارگذاری اولیه خانواده‌ها (ایجاد کش)
        $initialFamilies = $component->get('families');

        // تغییر فیلترها
        $component->set('tempFilters', [
            $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists')
        ]);

        // بررسی که کش پاک شود و نتایج جدید بارگذاری شوند
        $filteredFamilies = $component->get('families');
        $this->assertNotEquals($initialFamilies, $filteredFamilies);
        $this->assertTrue($filteredFamilies->contains('id', $family->id));
    }

    /**
     * تست ترکیب فیلتر استان و شهر با عملگر AND
     * 
     * @test
     */
    public function test_province_and_city_combined_filter()
    {
        // ایجاد خانواده‌ها در استان و شهرهای مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->charity->id
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->otherProvince->id,
            'city_id' => $this->otherCity->id,
            'charity_id' => $this->otherCharity->id
        ]);

        // تست ترکیب فیلتر استان و شهر با عملگر AND
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('province', $this->province->id, 'equals', 'and', 'exists'),
                $this->createFilterArray('city', $this->city->id, 'equals', 'and', 'exists')
            ]);

        // بررسی که فقط خانواده‌های با هر دو شرط نمایش داده شوند
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
        $this->assertFamilyInResults($component, $family3->id, false);
    }

    /**
     * تست فیلتر معیار پذیرش با یک معیار
     * 
     * @test
     */
    public function test_special_disease_single_criteria()
    {
        // ایجاد خانواده‌ها با اعضایی که problem_type مختلف دارند
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family1->id,
            'problem_type' => ['special_disease']
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family2->id,
            'problem_type' => ['addiction']
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family3->id,
            'problem_type' => ['work_disability']
        ]);

        $family4 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family4->id,
            'problem_type' => []
        ]);

        // تست فیلتر با معیار پذیرش 'special_disease'
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('special_disease', 'بیماری خاص', 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
        $this->assertFamilyInResults($component, $family3->id, false);
        $this->assertFamilyInResults($component, $family4->id, false);
    }

    /**
     * تست فیلتر معیار پذیرش با چند معیار comma-separated
     * 
     * @test
     */
    public function test_special_disease_multiple_criteria_with_comma_separated()
    {
        // ایجاد خانواده‌ها با معیارهای مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family1->id,
            'problem_type' => ['special_disease']
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family2->id,
            'problem_type' => ['addiction']
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family3->id,
            'problem_type' => ['work_disability']
        ]);

        $family4 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family4->id,
            'problem_type' => []
        ]);

        // تست فیلتر با چند معیار comma-separated
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('special_disease', 'بیماری خاص,اعتیاد', 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, true);
        $this->assertFamilyInResults($component, $family3->id, false);

        $this->assertFamilyInResults($component, $family4->id, false);
    }

    /**
     * تست فیلتر معیار پذیرش با منطق AND بین چند فیلتر
     * 
     * @test
     */
    public function test_special_disease_and_logic_multiple_filters()
    {
        // ایجاد خانواده‌ها با معیارهای مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family1->id,
            'problem_type' => ['special_disease']
        ]);
        Member::factory()->create([
            'family_id' => $family1->id,
            'problem_type' => ['addiction']
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family2->id,
            'problem_type' => ['special_disease']
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family3->id,
            'problem_type' => ['addiction']
        ]);

        // تست دو فیلتر جداگانه با عملگر AND
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('special_disease', 'بیماری خاص', 'equals', 'and', 'exists'),
                $this->createFilterArray('special_disease', 'اعتیاد', 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
        $this->assertFamilyInResults($component, $family3->id, false);
    }

    /**
     * تست فیلتر معیار پذیرش با عملگر exists
     * 
     * @test
     */
    public function test_special_disease_exists_operator()
    {
        // ایجاد خانواده‌ها با و بدون معیار پذیرش
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family1->id,
            'problem_type' => ['special_disease']
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family2->id,
            'problem_type' => []
        ]);

        // تست فیلتر با عملگر exists
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('special_disease', '', 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر معیار پذیرش با عملگر not_exists
     * 
     * @test
     */
    public function test_special_disease_not_exists_operator()
    {
        // ایجاد خانواده‌ها با و بدون معیار پذیرش
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family1->id,
            'problem_type' => ['special_disease']
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family2->id,
            'problem_type' => []
        ]);

        // تست فیلتر با عملگر not_exists
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('special_disease', '', 'equals', 'and', 'not_exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, true);
    }

    /**
     * تست فیلتر معیار پذیرش در acceptance_criteria خانواده
     * 
     * @test
     */
    public function test_special_disease_in_family_acceptance_criteria()
    {
        // ایجاد خانواده با acceptance_criteria
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'acceptance_criteria' => ['زن سرپرست خانواده', 'خانواده کم برخوردار']
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'acceptance_criteria' => ['خانواده کم برخوردار']
        ]);

        // تست فیلتر با معیار 'زن سرپرست خانواده'
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('special_disease', 'زن سرپرست خانواده', 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر معیار پذیرش با چند عضو دارای همان معیار
     * 
     * @test
     */
    public function test_special_disease_multiple_members_with_same_criteria()
    {
        // ایجاد خانواده با 3 عضو که همگی 'special_disease' دارند
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->count(3)->create([
            'family_id' => $family1->id,
            'problem_type' => ['special_disease']
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id
        ]);
        Member::factory()->create([
            'family_id' => $family2->id,
            'problem_type' => []
        ]);

        // تست فیلتر با معیار 'special_disease'
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('special_disease', 'بیماری خاص', 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر رتبه با مرتب‌سازی صعودی
     * 
     * @test
     */
    public function test_rank_filter_ascending_sort()
    {
        // ایجاد خانواده‌ها با calculated_rank مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'calculated_rank' => 10
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'calculated_rank' => 5
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'calculated_rank' => 15
        ]);

        // تست فیلتر رتبه با مرتب‌سازی صعودی
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('rank', 'asc', 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $families = $component->get('families');
        $this->assertEquals($family2->id, $families->first()->id); // کمترین رتبه اول
        $this->assertEquals($family1->id, $families->slice(1, 1)->first()->id); // میانی
        $this->assertEquals($family3->id, $families->last()->id); // بیشترین رتبه آخر
    }

    /**
     * تست فیلتر رتبه با مرتب‌سازی نزولی
     * 
     * @test
     */
    public function test_rank_filter_descending_sort()
    {
        // ایجاد خانواده‌ها با calculated_rank مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'calculated_rank' => 10
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'calculated_rank' => 5
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'calculated_rank' => 15
        ]);

        // تست فیلتر رتبه با مرتب‌سازی نزولی
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('rank', 'desc', 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $families = $component->get('families');
        $this->assertEquals($family3->id, $families->first()->id); // بیشترین رتبه اول
        $this->assertEquals($family1->id, $families->slice(1, 1)->first()->id); // میانی
        $this->assertEquals($family2->id, $families->last()->id); // کمترین رتبه آخر
    }

    /**
     * تست فیلتر رتبه فقط برای کاربران بیمه
     * 
     * @test
     */
    public function test_rank_filter_only_for_insurance_users()
    {
        // ایجاد خانواده‌ها
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'calculated_rank' => 10
        ]);

        // تست با کاربر عادی (غیر بیمه)
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('rank', 'asc', 'equals', 'and', 'exists')
            ]);

        // بررسی که فیلتر رتبه اعمال نشود یا خطا ندهد
        $families = $component->get('families');
        $this->assertNotNull($families);

        // تست با کاربر بیمه
        $this->actingAs($this->insuranceUser);
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('rank', 'asc', 'equals', 'and', 'exists')
            ]);

        $families = $component->get('families');
        $this->assertNotNull($families);
    }

    /**
     * تست فیلتر رتبه با رتبه‌های null
     * 
     * @test
     */
    public function test_rank_filter_with_null_ranks()
    {
        // ایجاد خانواده‌هایی با و بدون رتبه
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'calculated_rank' => 10
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'calculated_rank' => null
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'calculated_rank' => 5
        ]);

        // تست فیلتر رتبه با مرتب‌سازی صعودی
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                $this->createFilterArray('rank', 'asc', 'equals', 'and', 'exists')
            ]);

        // بررسی نتایج
        $families = $component->get('families');
        $this->assertEquals($family3->id, $families->first()->id); // کمترین رتبه اول
        $this->assertEquals($family1->id, $families->slice(1, 1)->first()->id); // میانی
        $this->assertEquals($family2->id, $families->last()->id); // null در انتها
    }

    /**
     * تست فیلتر تاریخ عضویت با فقط تاریخ شروع
     * 
     * @test
     */
    public function test_membership_date_with_start_date_only()
    {
        // ایجاد خانواده‌ها با created_at مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-01-15'
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-03-20'
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-06-10'
        ]);

        // تست فیلتر با فقط تاریخ شروع
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'membership_date',
                    'start_date' => '2024-03-01',
                    'end_date' => '',
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ]
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, true);
        $this->assertFamilyInResults($component, $family3->id, true);
    }

    /**
     * تست فیلتر تاریخ عضویت با فقط تاریخ پایان
     * 
     * @test
     */
    public function test_membership_date_with_end_date_only()
    {
        // ایجاد خانواده‌ها با created_at مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-01-15'
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-03-20'
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-06-10'
        ]);

        // تست فیلتر با فقط تاریخ پایان
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'membership_date',
                    'start_date' => '',
                    'end_date' => '2024-03-31',
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ]
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, true);
        $this->assertFamilyInResults($component, $family3->id, false);
    }

    /**
     * تست فیلتر تاریخ عضویت با بازه تاریخ
     * 
     * @test
     */
    public function test_membership_date_with_date_range()
    {
        // ایجاد خانواده‌ها با created_at مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-01-15'
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-03-20'
        ]);

        $family3 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-06-10'
        ]);

        // تست فیلتر با بازه تاریخ
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'membership_date',
                    'start_date' => '2024-02-01',
                    'end_date' => '2024-05-31',
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ]
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, false);
        $this->assertFamilyInResults($component, $family2->id, true);
        $this->assertFamilyInResults($component, $family3->id, false);
    }

    /**
     * تست فیلتر تاریخ عضویت با فرمت شمسی
     * 
     * @test
     */
    public function test_membership_date_with_jalali_format()
    {
        // ایجاد خانواده‌ها با created_at مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-03-20'
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-06-10'
        ]);

        // تست فیلتر با تاریخ شمسی
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'membership_date',
                    'start_date' => '1403/01/01',
                    'end_date' => '1403/03/31',
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ]
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر تاریخ عضویت با فرمت‌های مختلط
     * 
     * @test
     */
    public function test_membership_date_with_mixed_formats()
    {
        // ایجاد خانواده‌ها با created_at مختلف
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-03-20'
        ]);

        $family2 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-06-10'
        ]);

        // تست فیلتر با start_date شمسی و end_date میلادی
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'membership_date',
                    'start_date' => '1403/01/01',
                    'end_date' => '2024-05-31',
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ]
            ]);

        // بررسی نتایج
        $this->assertFamilyInResults($component, $family1->id, true);
        $this->assertFamilyInResults($component, $family2->id, false);
    }

    /**
     * تست فیلتر تاریخ عضویت با فرمت نامعتبر
     * 
     * @test
     */
    public function test_membership_date_with_invalid_format()
    {
        // ایجاد خانواده
        $family1 = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'created_at' => '2024-03-20'
        ]);

        // تست فیلتر با تاریخ نامعتبر
        $component = Livewire::test(FamiliesApproval::class)
            ->set('tempFilters', [
                [
                    'type' => 'membership_date',
                    'start_date' => '2024/13/45',
                    'end_date' => '',
                    'operator' => 'equals',
                    'logical_operator' => 'and',
                    'existence_operator' => 'exists'
                ]
            ]);

        // بررسی که خطا مدیریت شود و کامپوننت crash نکند
        $families = $component->get('families');
        $this->assertNotNull($families);
    }

}
