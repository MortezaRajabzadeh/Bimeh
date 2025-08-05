<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Member;
use App\Models\Family;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Charity\FamilyWizard;

class MemberNationalCodeUniquenessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create([
            'organization_id' => 1,
            'role' => 'charity'
        ]);
        $this->actingAs($user);
    }

    /** @test */
    public function it_prevents_duplicate_national_code_in_database()
    {
        // ایجاد یک عضو با کد ملی تکراری
        Member::factory()->create([
            'national_code' => '1234567890',
            'first_name' => 'فرد',
            'last_name' => 'موجود',
            'relationship' => 'head'
        ]);

        // تلاش برای ثبت عضو جدید با کد ملی تکراری
        $response = Livewire::test(FamilyWizard::class)
            ->set('province_id', 1)
            ->set('city_id', 1)
            ->set('district_id', 1)
            ->set('address', 'تست آدرس')
            ->set('members', [
                [
                    'first_name' => 'علی',
                    'last_name' => 'احمدی',
                    'national_code' => '1234567890',
                    'relationship' => 'head',
                    'birth_date' => '1990-01-01',
                    'gender' => 'male',
                    'marital_status' => 'single'
                ]
            ])
            ->set('head_member_index', 0)
            ->call('submit');

        // بررسی نمایش پیام خطا
        $response->assertStatus(200); // Livewire معمولاً 200 برمی‌گرداند حتی در صورت خطا
        $response->assertSee('کد ملی 1234567890 قبلاً در سیستم ثبت شده است');
        
        // بررسی لاگ خطا
        $this->assertDatabaseHas('failed_jobs', [
            'payload' => '%1234567890%',
            'exception' => '%ConflictHttpException%'
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_national_code_within_same_family()
    {
        Livewire::test(FamilyWizard::class)
            ->set('province_id', 1)
            ->set('city_id', 1)
            ->set('district_id', 1)
            ->set('address', 'تست آدرس')
            ->set('members', [
                [
                    'first_name' => 'علی',
                    'last_name' => 'احمدی',
                    'national_code' => '1234567890',
                    'relationship' => 'head',
                    'birth_date' => '1990-01-01',
                    'gender' => 'male',
                    'marital_status' => 'single'
                ],
                [
                    'first_name' => 'زهرا',
                    'last_name' => 'احمدی',
                    'national_code' => '1234567890',
                    'relationship' => 'spouse',
                    'birth_date' => '1992-01-01',
                    'gender' => 'female',
                    'marital_status' => 'married'
                ]
            ])
            ->set('head_member_index', 0)
            ->call('submit')
            ->assertSee('کد ملی 1234567890 قبلاً در سیستم ثبت شده است.');
    }
}
