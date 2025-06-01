<?php

namespace Tests\Feature;

use App\Models\Family;
use App\Models\FamilyInsurance;
use App\Models\FundingSource;
use App\Models\InsuranceShare;
use App\Models\User;
use App\Services\InsuranceShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class InsuranceShareTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $family;
    protected $fundingSource1;
    protected $fundingSource2;

    protected function setUp(): void
    {
        parent::setUp();

        // ایجاد کاربر و لاگین کردن
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // ایجاد خانواده
        $this->family = Family::factory()->create([
            'status' => 'reviewing'
        ]);

        // ایجاد بیمه برای خانواده
        FamilyInsurance::factory()->create([
            'family_id' => $this->family->id,
            'premium_amount' => 1000000 // یک میلیون تومان
        ]);

        // ایجاد منابع مالی
        $this->fundingSource1 = FundingSource::factory()->create([
            'name' => 'منبع مالی تست 1',
            'annual_budget' => 10000000
        ]);

        $this->fundingSource2 = FundingSource::factory()->create([
            'name' => 'منبع مالی تست 2',
            'annual_budget' => 10000000
        ]);
    }

    /** @test */
    public function it_can_allocate_shares_to_families()
    {
        $service = new InsuranceShareService();

        $shares = [
            [
                'funding_source_id' => $this->fundingSource1->id,
                'percentage' => 60,
                'description' => 'سهم تست 1'
            ],
            [
                'funding_source_id' => $this->fundingSource2->id,
                'percentage' => 40,
                'description' => 'سهم تست 2'
            ]
        ];

        $result = $service->allocate(collect([$this->family]), $shares);

        $this->assertCount(2, $result);
        $this->assertDatabaseHas('insurance_shares', [
            'family_id' => $this->family->id,
            'funding_source_id' => $this->fundingSource1->id,
            'percentage' => 60,
            'amount' => 600000
        ]);
        $this->assertDatabaseHas('insurance_shares', [
            'family_id' => $this->family->id,
            'funding_source_id' => $this->fundingSource2->id,
            'percentage' => 40,
            'amount' => 400000
        ]);

        // بررسی تغییر وضعیت خانواده
        $this->family->refresh();
        $this->assertEquals('approved', $this->family->status);
    }

    /** @test */
    public function it_validates_total_percentage_is_exactly_100()
    {
        $service = new InsuranceShareService();

        $shares = [
            [
                'funding_source_id' => $this->fundingSource1->id,
                'percentage' => 60,
                'description' => 'سهم تست 1'
            ],
            [
                'funding_source_id' => $this->fundingSource2->id,
                'percentage' => 30,
                'description' => 'سهم تست 2'
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('جمع درصدها باید دقیقاً ۱۰۰٪ باشد');

        $service->allocate(collect([$this->family]), $shares);

        // اطمینان از اینکه هیچ سهمی ثبت نشده
        $this->assertDatabaseMissing('insurance_shares', [
            'family_id' => $this->family->id
        ]);
    }

    /** @test */
    public function it_prevents_duplicate_allocations()
    {
        $service = new InsuranceShareService();

        $shares = [
            [
                'funding_source_id' => $this->fundingSource1->id,
                'percentage' => 60,
                'description' => 'سهم تست 1'
            ],
            [
                'funding_source_id' => $this->fundingSource2->id,
                'percentage' => 40,
                'description' => 'سهم تست 2'
            ]
        ];

        // تخصیص اول
        $service->allocate(collect([$this->family]), $shares);

        // اطمینان از ایجاد دو سهم
        $this->assertEquals(2, InsuranceShare::count());

        // تخصیص دوم با همان پارامترها
        $service->allocate(collect([$this->family]), $shares);

        // اطمینان از اینکه هیچ سهم جدیدی ایجاد نشده
        $this->assertEquals(2, InsuranceShare::count());
    }

    /** @test */
    public function it_allocates_shares_to_multiple_families()
    {
        $family2 = Family::factory()->create([
            'status' => 'reviewing'
        ]);

        FamilyInsurance::factory()->create([
            'family_id' => $family2->id,
            'premium_amount' => 2000000 // دو میلیون تومان
        ]);

        $service = new InsuranceShareService();

        $shares = [
            [
                'funding_source_id' => $this->fundingSource1->id,
                'percentage' => 70,
                'description' => 'سهم تست 1'
            ],
            [
                'funding_source_id' => $this->fundingSource2->id,
                'percentage' => 30,
                'description' => 'سهم تست 2'
            ]
        ];

        $result = $service->allocate(collect([$this->family, $family2]), $shares);

        $this->assertCount(4, $result);
        
        // بررسی سهم خانواده اول
        $this->assertDatabaseHas('insurance_shares', [
            'family_id' => $this->family->id,
            'funding_source_id' => $this->fundingSource1->id,
            'percentage' => 70,
            'amount' => 700000
        ]);

        // بررسی سهم خانواده دوم
        $this->assertDatabaseHas('insurance_shares', [
            'family_id' => $family2->id,
            'funding_source_id' => $this->fundingSource1->id,
            'percentage' => 70,
            'amount' => 1400000
        ]);
    }
} 