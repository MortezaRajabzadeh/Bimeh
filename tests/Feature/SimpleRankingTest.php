<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Family;
use App\Models\RankSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleRankingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // ایجاد کاربر تست
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_sort_families_by_selected_criteria_weights()
    {
        // ایجاد معیارهای رتبه‌بندی
        $criteria1 = RankSetting::factory()->create([
            'name' => 'زن سرپرست',
            'weight' => 10,
            'is_active' => true
        ]);
        
        $criteria2 = RankSetting::factory()->create([
            'name' => 'بیکاری',
            'weight' => 2,
            'is_active' => true
        ]);

        // ایجاد خانواده‌ها با معیارهای مختلف
        $family1 = Family::factory()->create([
            'acceptance_criteria' => [$criteria1->id] // امتیاز: 10
        ]);
        
        $family2 = Family::factory()->create([
            'acceptance_criteria' => [$criteria2->id] // امتیاز: 2
        ]);
        
        $family3 = Family::factory()->create([
            'acceptance_criteria' => [$criteria1->id, $criteria2->id] // امتیاز: 12
        ]);

        // تست سورت
        $families = Family::query()
            ->addSelect(\DB::raw("
                COALESCE(
                    (
                        SELECT SUM(rs.weight)
                        FROM rank_settings rs
                        WHERE rs.id IN ({$criteria1->id}, {$criteria2->id})
                        AND JSON_CONTAINS(families.acceptance_criteria, CAST(rs.id AS JSON))
                        AND rs.is_active = 1
                    ), 0
                ) as weighted_score
            "))
            ->orderBy('weighted_score', 'desc')
            ->get();

        // بررسی ترتیب صحیح (امتیاز بالاتر اول)
        $this->assertEquals($family3->id, $families[0]->id); // امتیاز 12
        $this->assertEquals($family1->id, $families[1]->id); // امتیاز 10
        $this->assertEquals($family2->id, $families[2]->id); // امتیاز 2
    }

    /** @test */
    public function it_handles_families_without_criteria()
    {
        // ایجاد خانواده بدون معیار
        $family = Family::factory()->create([
            'acceptance_criteria' => []
        ]);

        // تست سورت
        $families = Family::query()
            ->addSelect(\DB::raw("
                COALESCE(
                    (
                        SELECT SUM(rs.weight)
                        FROM rank_settings rs
                        WHERE rs.id IN (1, 2)
                        AND JSON_CONTAINS(families.acceptance_criteria, CAST(rs.id AS JSON))
                        AND rs.is_active = 1
                    ), 0
                ) as weighted_score
            "))
            ->orderBy('weighted_score', 'desc')
            ->get();

        // خانواده بدون معیار باید امتیاز 0 داشته باشد
        $this->assertEquals(0, $families[0]->weighted_score);
    }

    /** @test */
    public function it_uses_created_at_as_secondary_sort()
    {
        // ایجاد خانواده‌ها با امتیاز یکسان اما تاریخ‌های مختلف
        $criteria = RankSetting::factory()->create([
            'name' => 'زن سرپرست',
            'weight' => 10,
            'is_active' => true
        ]);

        $family1 = Family::factory()->create([
            'acceptance_criteria' => [$criteria->id],
            'created_at' => now()->subDays(2)
        ]);
        
        $family2 = Family::factory()->create([
            'acceptance_criteria' => [$criteria->id],
            'created_at' => now()->subDays(1)
        ]);

        // تست سورت با سورت ثانویه
        $families = Family::query()
            ->addSelect(\DB::raw("
                COALESCE(
                    (
                        SELECT SUM(rs.weight)
                        FROM rank_settings rs
                        WHERE rs.id IN ({$criteria->id})
                        AND JSON_CONTAINS(families.acceptance_criteria, CAST(rs.id AS JSON))
                        AND rs.is_active = 1
                    ), 0
                ) as weighted_score
            "))
            ->orderBy('weighted_score', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // خانواده قدیمی‌تر باید اول باشد (desc)
        $this->assertEquals($family2->id, $families[0]->id);
        $this->assertEquals($family1->id, $families[1]->id);
    }
} 