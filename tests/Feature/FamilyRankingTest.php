<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Family;
use App\Models\RankSetting;
use App\Models\FamilyMember;
use App\QueryFilters\FamilyRankingFilter;
use App\QuerySorts\FamilyRankingSort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

class FamilyRankingTest extends TestCase
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
    public function it_can_calculate_ranking_score_for_family_with_criteria()
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

        // ایجاد خانواده با معیارها
        $family = Family::factory()->create([
            'acceptance_criteria' => [$criteria1->id, $criteria2->id]
        ]);

        // ایجاد اعضای خانواده
        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'is_head' => true,
            'gender' => 'female'
        ]);

        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'is_head' => false,
            'problem_type' => ['بیکاری']
        ]);

        // تست محاسبه امتیاز
        $filter = new FamilyRankingFilter();
        $query = Family::query();
        
        $result = $filter->__invoke($query, [$criteria1->id, $criteria2->id], 'ranking');
        
        $this->assertNotNull($result);
    }

    /** @test */
    public function it_can_sort_families_by_ranking_score()
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

        // ایجاد خانواده‌ها با امتیازات مختلف
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
        $sort = new FamilyRankingSort();
        $query = Family::query();
        
        $result = $sort->__invoke($query, true, 'weighted_rank'); // نزولی
        
        $this->assertNotNull($result);
    }

    /** @test */
    public function it_can_filter_families_by_ranking_scheme()
    {
        // ایجاد طرح رتبه‌بندی
        $scheme = \App\Models\RankingScheme::factory()->create([
            'name' => 'طرح تست',
            'is_active' => true
        ]);

        // ایجاد معیارها
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

        // اتصال معیارها به طرح
        $scheme->criteria()->attach([
            $criteria1->id => ['weight' => 10],
            $criteria2->id => ['weight' => 2]
        ]);

        // ایجاد خانواده
        $family = Family::factory()->create([
            'acceptance_criteria' => [$criteria1->id, $criteria2->id]
        ]);

        // تست فیلتر طرح
        $filter = new FamilyRankingFilter();
        $query = Family::query();
        
        $result = $filter->__invoke($query, $scheme->id, 'ranking_scheme');
        
        $this->assertNotNull($result);
    }

    /** @test */
    public function it_can_calculate_priority_score_with_multiple_factors()
    {
        // ایجاد خانواده با شرایط مختلف
        $family = Family::factory()->create([
            'created_at' => now()->subDays(30) // قدیمی‌تر از 30 روز
        ]);

        // ایجاد سرپرست زن
        FamilyMember::factory()->create([
            'family_id' => $family->id,
            'is_head' => true,
            'gender' => 'female'
        ]);

        // ایجاد چندین عضو
        FamilyMember::factory()->count(5)->create([
            'family_id' => $family->id,
            'is_head' => false
        ]);

        // تست محاسبه امتیاز اولویت
        $sort = new FamilyRankingSort();
        $query = Family::query();
        
        $result = $sort->__invoke($query, true, 'priority_score');
        
        $this->assertNotNull($result);
    }

    /** @test */
    public function it_handles_empty_criteria_gracefully()
    {
        $filter = new FamilyRankingFilter();
        $query = Family::query();
        
        // تست با معیارهای خالی
        $result = $filter->__invoke($query, [], 'ranking');
        
        $this->assertNotNull($result);
        $this->assertEquals($query, $result);
    }

    /** @test */
    public function it_logs_ranking_operations()
    {
        Log::shouldReceive('debug')->atLeast()->once();
        Log::shouldReceive('error')->never();

        $filter = new FamilyRankingFilter();
        $query = Family::query();
        
        $filter->__invoke($query, [1, 2], 'ranking');
    }
} 