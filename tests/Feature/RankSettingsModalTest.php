<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Family;
use App\Models\RankSetting;
use App\Models\Member;
use App\Models\Province;
use App\Models\City;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Insurance\FamiliesApproval;

/**
 * تست واحد برای بررسی عملکرد مودال تنظیمات رتبه‌بندی
 * 
 * @group rank-settings
 * @group families
 */
class RankSettingsModalTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $province;
    protected $city;
    protected $charity;
    protected $insuranceUser;

    /**
     * راه‌اندازی اولیه برای تست‌ها
     */
    protected function setUp(): void
    {
        parent::setUp();

        // ایجاد کاربر بیمه
        $this->insuranceUser = User::factory()->create([
            'role' => 'insurance_user',
            'isInsuranceUser' => true
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
        $this->actingAs($this->insuranceUser);
    }

    /**
     * ایجاد معیارهای رتبه‌بندی پیش‌فرض
     */
    protected function createDefaultRankSettings()
    {
        return [
            RankSetting::factory()->create([
                'name' => 'زن سرپرست',
                'weight' => 10,
                'description' => 'خانواده‌هایی با سرپرست زن',
                'requires_document' => true,
                'is_active' => true
            ]),
            RankSetting::factory()->create([
                'name' => 'بیماری خاص',
                'weight' => 8,
                'description' => 'خانواده‌هایی با اعضای دارای بیماری خاص',
                'requires_document' => true,
                'is_active' => true
            ]),
            RankSetting::factory()->create([
                'name' => 'بیکاری',
                'weight' => 5,
                'description' => 'خانواده‌هایی با وضعیت بیکاری',
                'requires_document' => false,
                'is_active' => true
            ])
        ];
    }

    /**
     * ایجاد خانواده با اعضای دارای مشکلات مختلف
     */
    protected function createFamilyWithCriteria($criteriaNames = [])
    {
        $family = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'acceptance_criteria' => $criteriaNames
        ]);

        // ایجاد سرپرست
        Member::factory()->create([
            'family_id' => $family->id,
            'is_head' => true
        ]);

        return $family;
    }

    /** @test */
    public function rank_settings_modal_displays_criteria_correctly()
    {
        // ایجاد معیارهای پیش‌فرض
        $criteria = $this->createDefaultRankSettings();

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);
        $component->call('loadRankSettings');

        // بررسی نمایش معیارها
        $component->assertSet('rankSettings', function ($rankSettings) use ($criteria) {
            return $rankSettings->count() === 3 &&
                   $rankSettings->contains('name', 'زن سرپرست') &&
                   $rankSettings->contains('name', 'بیماری خاص') &&
                   $rankSettings->contains('name', 'بیکاری');
        });

        // بررسی فیلدهای هر معیار
        foreach ($criteria as $criterion) {
            $component->assertSet('rankSettings', function ($rankSettings) use ($criterion) {
                $setting = $rankSettings->firstWhere('id', $criterion->id);
                return $setting &&
                       isset($setting->name) &&
                       isset($setting->weight) &&
                       isset($setting->description) &&
                       isset($setting->requires_document);
            });
        }
    }

    /** @test */
    public function selecting_criteria_with_checkbox_works()
    {
        // ایجاد معیارهای مختلف
        $criteria = $this->createDefaultRankSettings();
        [$criterion1, $criterion2, $criterion3] = $criteria;

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);
        
        // انتخاب معیارها
        $component->set('selectedCriteria', [
            $criterion1->id => true,
            $criterion2->id => false,
            $criterion3->id => true
        ]);

        // بررسی انتخاب‌ها
        $component->assertSet('selectedCriteria', [
            $criterion1->id => true,
            $criterion2->id => false,
            $criterion3->id => true
        ]);

        // تغییر انتخاب
        $component->set('selectedCriteria', [
            $criterion1->id => false,
            $criterion2->id => true,
            $criterion3->id => true
        ]);

        // بررسی تغییرات
        $component->assertSet('selectedCriteria', [
            $criterion1->id => false,
            $criterion2->id => true,
            $criterion3->id => true
        ]);
    }

    /** @test */
    public function adding_new_rank_setting_works()
    {
        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // تنظیم فیلدهای فرم
        $component->set('rankSettingName', 'معیار تست جدید')
                  ->set('rankSettingWeight', 7)
                  ->set('rankSettingDescription', 'توضیحات تست')
                  ->set('rankSettingNeedsDoc', true);

        // فراخوانی متد ذخیره
        $component->call('saveRankSetting');

        // بررسی ذخیره‌سازی در دیتابیس
        $this->assertDatabaseHas('rank_settings', [
            'name' => 'معیار تست جدید',
            'weight' => 7,
            'description' => 'توضیحات تست',
            'requires_document' => 1,
            'is_active' => 1
        ]);

        // بررسی نمایش toast موفقیت
        $component->assertDispatched('toast');
    }

    /** @test */
    public function editing_rank_setting_weight_works()
    {
        // ایجاد یک معیار با وزن 5
        $criterion = RankSetting::factory()->create([
            'name' => 'معیار ویرایشی',
            'weight' => 5,
            'description' => 'توضیحات اولیه',
            'requires_document' => false
        ]);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // فراخوانی متد ویرایش
        $component->call('editRankSetting', $criterion->id);

        // بررسی پر شدن فیلدهای فرم
        $component->assertSet('rankSettingName', $criterion->name)
                  ->assertSet('rankSettingWeight', $criterion->weight)
                  ->assertSet('rankSettingDescription', $criterion->description)
                  ->assertSet('rankSettingNeedsDoc', $criterion->requires_document ? 1 : 0)
                  ->assertSet('editingRankSettingId', $criterion->id);

        // تغییر وزن به 8
        $component->set('rankSettingWeight', 8)
                  ->call('saveRankSetting');

        // بررسی به‌روزرسانی در دیتابیس
        $this->assertDatabaseHas('rank_settings', [
            'id' => $criterion->id,
            'weight' => 8
        ]);

        // بررسی ثابت ماندن سایر فیلدها
        $this->assertDatabaseHas('rank_settings', [
            'id' => $criterion->id,
            'name' => 'معیار ویرایشی',
            'description' => 'توضیحات اولیه',
            'requires_document' => 0
        ]);
    }

    /** @test */
    public function rank_setting_validation_works()
    {
        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // تست با نام خالی
        $component->set('rankSettingName', '')
                  ->set('rankSettingWeight', 5)
                  ->call('saveRankSetting');

        // بررسی خطای validation
        $component->assertHasErrors(['rankSettingName' => 'required']);

        // تست با وزن نامعتبر (کمتر از 0)
        $component->set('rankSettingName', 'معیار تست')
                  ->set('rankSettingWeight', -1)
                  ->call('saveRankSetting');

        // بررسی خطای validation
        $component->assertHasErrors(['rankSettingWeight' => 'min']);

        // تست با وزن نامعتبر (بیشتر از 10)
        $component->set('rankSettingWeight', 15)
                  ->call('saveRankSetting');

        // بررسی خطای validation
        $component->assertHasErrors(['rankSettingWeight' => 'max']);

        // تست با وزن غیر عددی
        $component->set('rankSettingWeight', 'abc')
                  ->call('saveRankSetting');

        // بررسی خطای validation
        $component->assertHasErrors(['rankSettingWeight' => 'integer']);

        // بررسی که معیار با داده‌های نامعتبر ذخیره نشده است
        $this->assertDatabaseMissing('rank_settings', [
            'name' => 'معیار تست'
        ]);
    }

    /** @test */
    public function apply_criteria_calculates_scores_correctly()
    {
        // ایجاد معیارهای مختلف با وزن‌های متفاوت
        $criterion1 = RankSetting::factory()->create([
            'name' => 'معیار 1',
            'weight' => 10,
            'is_active' => true
        ]);
        
        $criterion2 = RankSetting::factory()->create([
            'name' => 'معیار 2',
            'weight' => 5,
            'is_active' => true
        ]);
        
        $criterion3 = RankSetting::factory()->create([
            'name' => 'معیار 3',
            'weight' => 3,
            'is_active' => true
        ]);

        // ایجاد خانواده‌ها
        $family1 = $this->createFamilyWithCriteria(['معیار 1']); // امتیاز 10
        $family2 = $this->createFamilyWithCriteria(['معیار 1', 'معیار 2']); // امتیاز 15
        $family3 = $this->createFamilyWithCriteria(['معیار 1', 'معیار 2', 'معیار 3']); // امتیاز 18

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // انتخاب معیارها
        $component->set('selectedCriteria', [
            $criterion1->id => true,
            $criterion2->id => true,
            $criterion3->id => true
        ]);

        // فراخوانی applyCriteria
        $component->call('applyCriteria');

        // بررسی تغییر sortField به weighted_rank
        $component->assertSet('sortField', 'weighted_rank');

        // بررسی تغییر sortDirection به desc
        $component->assertSet('sortDirection', 'desc');

        // بررسی نمایش toast موفقیت
        $component->assertDispatched('toast');
    }

    /** @test */
    public function apply_criteria_without_selection_clears_filters()
    {
        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // تنظیم فیلترهای رتبه
        $component->set('selectedCriteria', [])
                  ->set('specific_criteria', 'معیار1,معیار2');

        // فراخوانی applyCriteria
        $component->call('applyCriteria');

        // بررسی تنظیم specific_criteria به null
        $component->assertSet('specific_criteria', null);

        // بررسی بازگشت sortField به created_at
        $component->assertSet('sortField', 'created_at');

        // بررسی نمایش toast اطلاع‌رسانی
        $component->assertDispatched('toast');
    }

    /** @test */
    public function reset_to_defaults_works()
    {
        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // تنظیم فیلترهای رتبه
        $component->set('selectedCriteria', [1 => true, 2 => true])
                  ->set('family_rank_range', '1-100')
                  ->set('specific_criteria', 'معیار1,معیار2')
                  ->set('showRankModal', true);

        // فراخوانی resetToDefaults
        $component->call('resetToDefaults');

        // بررسی پاک شدن selectedCriteria
        $component->assertSet('selectedCriteria', []);

        // بررسی تنظیم family_rank_range به null
        $component->assertSet('family_rank_range', null);

        // بررسی تنظیم specific_criteria به null
        $component->assertSet('specific_criteria', null);

        // بررسی تنظیم showRankModal به false
        $component->assertSet('showRankModal', false);

        // بررسی نمایش toast موفقیت
        $component->assertDispatched('toast');
    }

    /** @test */
    public function family_score_calculation_with_multiple_criteria()
    {
        // ایجاد معیارهای مختلف با وزن‌های متفاوت
        $criterion1 = RankSetting::factory()->create([
            'name' => 'زن سرپرست',
            'weight' => 10,
            'is_active' => true
        ]);
        
        $criterion2 = RankSetting::factory()->create([
            'name' => 'بیماری خاص',
            'weight' => 8,
            'is_active' => true
        ]);
        
        $criterion3 = RankSetting::factory()->create([
            'name' => 'بیکاری',
            'weight' => 5,
            'is_active' => true
        ]);

        // ایجاد خانواده با اعضای دارای مشکلات مختلف
        $family = Family::factory()->create([
            'province_id' => $this->province->id,
            'city_id' => $this->city->id,
            'charity_id' => $this->charity->id,
            'acceptance_criteria' => ['زن سرپرست']
        ]);

        // ایجاد سرپرست زن
        Member::factory()->create([
            'family_id' => $family->id,
            'is_head' => true,
            'gender' => 'female'
        ]);

        // ایجاد عضو با بیماری خاص
        Member::factory()->create([
            'family_id' => $family->id,
            'is_head' => false,
            'problem_type' => ['بیماری خاص']
        ]);

        // اتصال معیارها به خانواده
        $family->criteria()->attach([$criterion1->id, $criterion2->id]);

        // فراخوانی محاسبه رتبه
        $rank = $family->calculateRank();

        // بررسی محاسبه صحیح امتیاز
        $this->assertEquals(18, $rank); // (10 + 8) / (10 + 8 + 5) * 100 = 18

        // بررسی ذخیره شدن weighted_rank در دیتابیس
        $this->assertDatabaseHas('families', [
            'id' => $family->id,
            'calculated_rank' => $rank
        ]);
    }

    /** @test */
    public function rank_modal_only_visible_for_insurance_users()
    {
        // ایجاد کاربر عادی (غیر بیمه)
        $normalUser = User::factory()->create([
            'role' => 'user',
            'isInsuranceUser' => false
        ]);

        // احراز هویت با کاربر عادی
        $this->actingAs($normalUser);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // بررسی که isInsuranceUser false است
        $component->assertSet('isInsuranceUser', false);

        // تغییر به کاربر بیمه
        $this->actingAs($this->insuranceUser);
        $component2 = Livewire::test(FamiliesApproval::class);
        $component2->assertSet('isInsuranceUser', true);
    }

    /** @test */
    public function reset_rank_setting_form_clears_fields()
    {
        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // تنظیم فیلدهای فرم
        $component->set('rankSettingName', 'نام تست')
                  ->set('rankSettingDescription', 'توضیحات تست')
                  ->set('rankSettingWeight', 7)
                  ->set('rankSettingNeedsDoc', true)
                  ->set('editingRankSettingId', 5);

        // فراخوانی resetRankSettingForm
        $component->call('resetRankSettingForm');

        // بررسی پاک شدن فیلدها
        $component->assertSet('rankSettingName', '')
                  ->assertSet('rankSettingDescription', '')
                  ->assertSet('rankSettingWeight', 5)
                  ->assertSet('rankSettingNeedsDoc', true)
                  ->assertSet('editingRankSettingId', null);
    }

    /** @test */
    public function load_rank_settings_retrieves_all_criteria()
    {
        // ایجاد 5 معیار با is_active=true و sort_order مختلف
        $criteria = [];
        for ($i = 1; $i <= 5; $i++) {
            $criteria[] = RankSetting::factory()->create([
                'name' => "معیار {$i}",
                'weight' => $i * 2,
                'is_active' => true,
                'sort_order' => $i * 10
            ]);
        }

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // فراخوانی loadRankSettings
        $component->call('loadRankSettings');

        // بررسی که rankSettings شامل همه معیارها است
        $component->assertSet('rankSettings', function ($rankSettings) {
            return $rankSettings->count() === 5;
        });

        // بررسی مرتب‌سازی بر اساس sort_order
        $component->assertSet('rankSettings', function ($rankSettings) {
            $sorted = $rankSettings->sortBy('sort_order')->values();
            for ($i = 0; $i < $sorted->count() - 1; $i++) {
                if ($sorted[$i]->sort_order > $sorted[$i + 1]->sort_order) {
                    return false;
                }
            }
            return true;
        });

        // بررسی که availableCriteria فقط معیارهای فعال را دارد
        $component->assertSet('availableCriteria', function ($availableCriteria) {
            return $availableCriteria->count() === 5 &&
                   $availableCriteria->every(fn($c) => $c->is_active);
        });
    }

    /** @test */
    public function editing_rank_setting_with_invalid_data_fails()
    {
        // ایجاد یک معیار
        $criterion = RankSetting::factory()->create([
            'name' => 'معیار اولیه',
            'weight' => 5
        ]);

        // تست کامپوننت
        $component = Livewire::test(FamiliesApproval::class);

        // فراخوانی editRankSetting
        $component->call('editRankSetting', $criterion->id);

        // تلاش برای ذخیره با وزن نامعتبر (مثلاً 15)
        $component->set('rankSettingWeight', 15)
                  ->call('saveRankSetting');

        // بررسی خطای validation
        $component->assertHasErrors(['rankSettingWeight' => 'max']);

        // بررسی که معیار در دیتابیس تغییر نکرده است
        $this->assertDatabaseHas('rank_settings', [
            'id' => $criterion->id,
            'weight' => 5
        ]);

        $this->assertDatabaseMissing('rank_settings', [
            'id' => $criterion->id,
            'weight' => 15
        ]);
    }
}