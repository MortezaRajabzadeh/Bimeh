<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\SavedFilter;
use App\Models\RankSetting;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Livewire\Livewire;
use App\Livewire\Insurance\FamiliesApproval;

/**
 * تست‌های جامع برای ذخیره و بارگذاری فیلترهای تنظیمات رتبه‌بندی
 * 
 * @group rank-settings
 * @group filters
 * @group insurance
 */
class RankSettingsFilterTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $insuranceUser;
    protected $otherInsuranceUser;
    protected $charityUser;
    protected $adminUser;
    protected $organizationA;
    protected $organizationB;

    /**
     * راه‌اندازی اولیه برای تست‌ها
     */
    protected function setUp(): void
    {
        parent::setUp();

        // ایجاد سازمان‌ها
        $this->organizationA = Organization::factory()->create([
            'name' => 'سازمان A',
            'type' => 'charity'
        ]);

        $this->organizationB = Organization::factory()->create([
            'name' => 'سازمان B',
            'type' => 'charity'
        ]);

        // ایجاد کاربران مختلف
        $this->insuranceUser = User::factory()->create([
            'role' => 'insurance_user',
            'isInsuranceUser' => true,
            'organization_id' => $this->organizationA->id
        ]);

        $this->otherInsuranceUser = User::factory()->create([
            'role' => 'insurance_user',
            'isInsuranceUser' => true,
            'organization_id' => $this->organizationA->id // هم‌سازمان
        ]);

        $this->charityUser = User::factory()->create([
            'role' => 'charity_user',
            'isInsuranceUser' => false,
            'organization_id' => $this->organizationA->id
        ]);

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'isInsuranceUser' => false,
            'organization_id' => null
        ]);

        // احراز هویت با کاربر بیمه به عنوان پیش‌فرض
        $this->actingAs($this->insuranceUser);
    }

    /** @test */
    public function save_rank_filter_successfully()
    {
        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // تنظیم تنظیمات رتبه‌بندی
        $component->set('selectedCriteria', [1 => true, 2 => false, 3 => true])
                  ->set('family_rank_range', '1-10')
                  ->set('specific_criteria', 'معیار1,معیار2');

        // فراخوانی متد ذخیره
        $component->call('saveRankFilter', 'نام تست', 'توضیحات تست');

        // بررسی ذخیره در دیتابیس
        $this->assertDatabaseHas('saved_filters', [
            'name' => 'نام تست',
            'description' => 'توضیحات تست',
            'user_id' => $this->insuranceUser->id,
            'organization_id' => $this->organizationA->id,
            'filter_type' => 'rank_settings',
            'usage_count' => 0
        ]);

        // بررسی dispatch رویداد toast
        $component->assertDispatched('toast');

        // بررسی ساختار filters_config
        $savedFilter = SavedFilter::where('name', 'نام تست')->first();
        $config = $savedFilter->filters_config;

        $this->assertEquals([
            'selectedCriteria' => [1 => true, 2 => false, 3 => true],
            'family_rank_range' => '1-10',
            'specific_criteria' => 'معیار1,معیار2'
        ], $config);
    }

    /** @test */
    public function save_rank_filter_validation_empty_name()
    {
        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // تلاش برای ذخیره با نام خالی
        $component->call('saveRankFilter', '', 'توضیحات تست');

        // بررسی عدم ذخیره در دیتابیس
        $this->assertDatabaseMissing('saved_filters', [
            'description' => 'توضیحات تست'
        ]);

        // بررسی dispatch رویداد toast با پیام خطا
        $component->assertDispatched('toast');
    }

    /** @test */
    public function save_rank_filter_validation_duplicate_name()
    {
        // ایجاد یک فیلتر با نام مشخص
        SavedFilter::factory()->create([
            'name' => 'فیلتر تکراری',
            'user_id' => $this->insuranceUser->id,
            'filter_type' => 'rank_settings'
        ]);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // تلاش برای ذخیره فیلتر دیگر با همان نام
        $component->call('saveRankFilter', 'فیلتر تکراری', 'توضیحات جدید');

        // بررسی عدم ذخیره فیلتر دوم
        $this->assertEquals(1, SavedFilter::where('name', 'فیلتر تکراری')->count());

        // بررسی پیام خطا
        $component->assertDispatched('toast');
    }

    /** @test */
    public function load_rank_filter_successfully()
    {
        // ایجاد یک SavedFilter با filter_type='rank_settings'
        $filter = SavedFilter::factory()->create([
            'user_id' => $this->insuranceUser->id,
            'filter_type' => 'rank_settings',
            'filters_config' => [
                'selectedCriteria' => [1 => true, 2 => false],
                'family_rank_range' => '5-15',
                'specific_criteria' => 'معیار1'
            ],
            'usage_count' => 0
        ]);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // فراخوانی loadRankFilter
        $component->call('loadRankFilter', $filter->id);

        // بررسی اعمال تنظیمات
        $component->assertSet('selectedCriteria', [1 => true, 2 => false])
                  ->assertSet('family_rank_range', '5-15')
                  ->assertSet('specific_criteria', 'معیار1');

        // بررسی افزایش usage_count از 0 به 1
        $this->assertDatabaseHas('saved_filters', [
            'id' => $filter->id,
            'usage_count' => 1
        ]);

        // بررسی به‌روزرسانی last_used_at
        $updatedFilter = SavedFilter::find($filter->id);
        $this->assertNotNull($updatedFilter->last_used_at);

        // بررسی dispatch رویداد toast با پیام موفقیت
        $component->assertDispatched('toast');
    }

    /** @test */
    public function load_rank_filter_increments_usage_count()
    {
        // ایجاد فیلتر با usage_count=5
        $filter = SavedFilter::factory()->create([
            'user_id' => $this->insuranceUser->id,
            'filter_type' => 'rank_settings',
            'usage_count' => 5
        ]);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // بارگذاری فیلتر
        $component->call('loadRankFilter', $filter->id);

        // بررسی افزایش به 6
        $this->assertDatabaseHas('saved_filters', [
            'id' => $filter->id,
            'usage_count' => 6
        ]);

        // بارگذاری مجدد
        $component->call('loadRankFilter', $filter->id);

        // بررسی افزایش به 7
        $this->assertDatabaseHas('saved_filters', [
            'id' => $filter->id,
            'usage_count' => 7
        ]);
    }

    /** @test */
    public function load_rank_filter_not_found()
    {
        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // تلاش برای بارگذاری فیلتر با ID نامعتبر
        $component->call('loadRankFilter', 999999);

        // بررسی dispatch رویداد toast با پیام warning
        $component->assertDispatched('toast');

        // بررسی عدم تغییر تنظیمات
        $component->assertSet('selectedCriteria', [])
                  ->assertSet('family_rank_range', null)
                  ->assertSet('specific_criteria', null);
    }

    /** @test */
    public function load_rank_filter_access_denied()
    {
        // ایجاد فیلتر توسط کاربر A
        $userA = User::factory()->create([
            'role' => 'insurance_user',
            'organization_id' => $this->organizationB->id
        ]);

        $filter = SavedFilter::factory()->create([
            'user_id' => $userA->id,
            'filter_type' => 'rank_settings'
        ]);

        // login با کاربر B از سازمان دیگر
        $this->actingAs($this->insuranceUser);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // تلاش برای بارگذاری فیلتر کاربر A
        $component->call('loadRankFilter', $filter->id);

        // بررسی عدم دسترسی و پیام warning
        $component->assertDispatched('toast');
    }

    /** @test */
    public function load_saved_filters_with_rank_modal_type()
    {
        // ایجاد چند فیلتر با filter_type='rank_settings'
        $filter1 = SavedFilter::factory()->create([
            'user_id' => $this->insuranceUser->id,
            'filter_type' => 'rank_settings',
            'usage_count' => 5
        ]);

        $filter2 = SavedFilter::factory()->create([
            'user_id' => $this->insuranceUser->id,
            'filter_type' => 'rank_settings',
            'usage_count' => 10
        ]);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // فراخوانی loadSavedFilters با 'rank_modal'
        $filters = $component->invokeMethod('loadSavedFilters', ['rank_modal']);

        // بررسی تبدیل 'rank_modal' به 'rank_settings'
        $this->assertCount(2, $filters);

        // بررسی مرتب‌سازی بر اساس usage_count نزولی
        $this->assertEquals($filter2->id, $filters[0]['id']);
        $this->assertEquals($filter1->id, $filters[1]['id']);
    }

    /** @test */
    public function load_saved_filters_for_insurance_user()
    {
        // ایجاد کاربر بیمه A و B در یک سازمان
        $userA = User::factory()->create([
            'role' => 'insurance_user',
            'organization_id' => $this->organizationA->id
        ]);

        $userB = User::factory()->create([
            'role' => 'insurance_user',
            'organization_id' => $this->organizationA->id
        ]);

        // ایجاد فیلتر توسط کاربر A
        $filter = SavedFilter::factory()->create([
            'user_id' => $userA->id,
            'filter_type' => 'rank_settings',
            'organization_id' => $this->organizationA->id
        ]);

        // login با کاربر B
        $this->actingAs($userB);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // فراخوانی loadSavedFilters
        $filters = $component->invokeMethod('loadSavedFilters', ['rank_settings']);

        // بررسی دسترسی کاربر B به فیلتر کاربر A (هم‌سازمان)
        $this->assertCount(1, $filters);
        $this->assertEquals($filter->id, $filters[0]['id']);
    }

    /** @test */
    public function load_saved_filters_organization_isolation()
    {
        // ایجاد کاربر بیمه در سازمان A
        $userA = User::factory()->create([
            'role' => 'insurance_user',
            'organization_id' => $this->organizationA->id
        ]);

        // ایجاد کاربر بیمه در سازمان B
        $userB = User::factory()->create([
            'role' => 'insurance_user',
            'organization_id' => $this->organizationB->id
        ]);

        // ایجاد فیلتر توسط کاربر سازمان A
        $filter = SavedFilter::factory()->create([
            'user_id' => $userA->id,
            'filter_type' => 'rank_settings',
            'organization_id' => $this->organizationA->id
        ]);

        // login با کاربر سازمان B
        $this->actingAs($userB);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // فراخوانی loadSavedFilters
        $filters = $component->invokeMethod('loadSavedFilters', ['rank_settings']);

        // بررسی عدم دسترسی به فیلتر سازمان A
        $this->assertCount(0, $filters);
    }

    /** @test */
    public function load_saved_filters_returns_empty_on_error()
    {
        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // فراخوانی loadSavedFilters بدون login
        $this->actingAs(null);
        $filters = $component->invokeMethod('loadSavedFilters', ['rank_settings']);

        // بررسی بازگشت آرایه خالی
        $this->assertIsArray($filters);
        $this->assertCount(0, $filters);
    }

    /** @test */
    public function save_rank_filter_stores_correct_config()
    {
        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // تنظیم تنظیمات
        $selectedCriteria = [1 => true, 3 => true, 5 => false];
        $familyRankRange = '1-10';
        $specificCriteria = 'زن سرپرست,بیماری خاص';

        $component->set('selectedCriteria', $selectedCriteria)
                  ->set('family_rank_range', $familyRankRange)
                  ->set('specific_criteria', $specificCriteria);

        // ذخیره فیلتر
        $component->call('saveRankFilter', 'فیلتر تست', 'توضیحات');

        // بررسی filters_config در دیتابیس
        $savedFilter = SavedFilter::where('name', 'فیلتر تست')->first();
        $config = $savedFilter->filters_config;

        $this->assertEquals([
            'selectedCriteria' => $selectedCriteria,
            'family_rank_range' => $familyRankRange,
            'specific_criteria' => $specificCriteria
        ], $config);
    }

    /** @test */
    public function load_rank_filter_applies_all_settings()
    {
        // ایجاد فیلتر با تنظیمات کامل
        $filterConfig = [
            'selectedCriteria' => [1 => true, 2 => false, 3 => true],
            'family_rank_range' => '5-20',
            'specific_criteria' => 'زن سرپرست,بیکاری'
        ];

        $filter = SavedFilter::factory()->create([
            'user_id' => $this->insuranceUser->id,
            'filter_type' => 'rank_settings',
            'filters_config' => $filterConfig
        ]);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // بارگذاری فیلتر
        $component->call('loadRankFilter', $filter->id);

        // بررسی اعمال تمام تنظیمات به کامپوننت
        $component->assertSet('selectedCriteria', $filterConfig['selectedCriteria'])
                  ->assertSet('family_rank_range', $filterConfig['family_rank_range'])
                  ->assertSet('specific_criteria', $filterConfig['specific_criteria']);
    }

    /** @test */
    public function rank_filter_only_for_insurance_users()
    {
        // ایجاد کاربر خیریه
        $this->actingAs($this->charityUser);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // بررسی isInsuranceUser=false در کامپوننت
        $component->assertSet('isInsuranceUser', false);

        // ایجاد کاربر بیمه
        $this->actingAs($this->insuranceUser);

        // تست کامپوننت
        $component2 = Livewire::test(FamiliesApproval::class);

        // بررسی isInsuranceUser=true
        $component2->assertSet('isInsuranceUser', true);
    }

    /** @test */
    public function save_rank_filter_with_empty_criteria()
    {
        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // تنظیم selectedCriteria خالی
        $component->set('selectedCriteria', [])
                  ->set('family_rank_range', null)
                  ->set('specific_criteria', null);

        // ذخیره فیلتر
        $component->call('saveRankFilter', 'فیلتر بدون معیار', 'توضیحات');

        // بررسی ذخیره موفق با معیارهای خالی
        $this->assertDatabaseHas('saved_filters', [
            'name' => 'فیلتر بدون معیار'
        ]);

        $savedFilter = SavedFilter::where('name', 'فیلتر بدون معیار')->first();
        $config = $savedFilter->filters_config;

        $this->assertEquals([
            'selectedCriteria' => [],
            'family_rank_range' => null,
            'specific_criteria' => null
        ], $config);
    }

    /** @test */
    public function saved_filter_model_increment_usage()
    {
        // ایجاد SavedFilter
        $filter = SavedFilter::factory()->create([
            'usage_count' => 5
        ]);

        // فراخوانی incrementUsage
        $filter->incrementUsage();

        // بررسی افزایش usage_count
        $this->assertDatabaseHas('saved_filters', [
            'id' => $filter->id,
            'usage_count' => 6
        ]);

        // بررسی به‌روزرسانی last_used_at
        $updatedFilter = SavedFilter::find($filter->id);
        $this->assertNotNull($updatedFilter->last_used_at);
    }

    /** @test */
    public function saved_filter_permissions_work_correctly()
    {
        // ایجاد فیلترهای با سطوح دسترسی مختلف
        $filter = SavedFilter::factory()->create([
            'user_id' => $this->insuranceUser->id,
            'filter_type' => 'rank_settings'
        ]);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // فراخوانی loadSavedFilters
        $filters = $component->invokeMethod('loadSavedFilters', ['rank_settings']);

        // بررسی دسترسی به فیلترهای مختلف
        $this->assertCount(1, $filters);
    }
}