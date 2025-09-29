<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Family;
use App\Models\Province;
use App\Models\User;
use App\Models\Organization;
use App\Livewire\Charity\FamilySearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;

class ProvinceFilteringTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $province;
    protected $testFamilies;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'user_type' => 'admin'
        ]);
        
        // Create test province (آذربایجان شرقی)
        $this->province = Province::create([
            'name' => 'آذربایجان شرقی'
        ]);
        
        // Create test organization
        $organization = Organization::factory()->create([
            'name' => 'Test Organization',
            'type' => 'charity'
        ]);
        
        // Create test families - some with آذربایجان شرقی, some without
        $this->testFamilies = collect([
            // 5 families from آذربایجان شرقی
            Family::factory()->count(5)->create([
                'province_id' => $this->province->id,
                'status' => 'pending'
            ]),
            
            // 3 families from other provinces
            Family::factory()->count(3)->create([
                'province_id' => Province::factory()->create(['name' => 'تهران'])->id,
                'status' => 'pending'
            ])
        ])->flatten();
    }

    /** @test */
    public function it_has_test_data_setup_correctly()
    {
        $this->assertEquals(1, $this->province->id);
        $this->assertEquals('آذربایجان شرقی', $this->province->name);
        $this->assertEquals(8, Family::count());
        $this->assertEquals(5, Family::where('province_id', $this->province->id)->count());
    }

    /** @test */
    public function it_filters_families_by_province_with_exists_operator()
    {
        Auth::login($this->user);
        
        $component = Livewire::test(FamilySearch::class);
        
        // Test with single filter: province = آذربایجان شرقی باشد
        $filters = [
            [
                'type' => 'province',
                'value' => $this->province->id,
                'existence_operator' => 'exists', // باشد
                'logical_operator' => 'and'
            ]
        ];
        
        $component->set('tempFilters', $filters);
        $component->call('applyFilters');
        
        $families = $component->get('families');
        
        // Should return 5 families from آذربایجان شرقی
        $this->assertEquals(5, $families->total());
        
        foreach ($families as $family) {
            $this->assertEquals($this->province->id, $family->province_id);
        }
    }

    /** @test */
    public function it_filters_families_by_province_with_not_exists_operator()
    {
        Auth::login($this->user);
        
        $component = Livewire::test(FamilySearch::class);
        
        // Test with single filter: province = آذربایجان شرقی نباشد
        $filters = [
            [
                'type' => 'province',
                'value' => $this->province->id,
                'existence_operator' => 'not_exists', // نباشد
                'logical_operator' => 'and'
            ]
        ];
        
        $component->set('tempFilters', $filters);
        $component->call('applyFilters');
        
        $families = $component->get('families');
        
        // Should return 3 families NOT from آذربایجان شرقی
        $this->assertEquals(3, $families->total());
        
        foreach ($families as $family) {
            $this->assertNotEquals($this->province->id, $family->province_id);
        }
    }

    /** @test */
    public function it_handles_legacy_equals_operator_correctly()
    {
        Auth::login($this->user);
        
        $component = Livewire::test(FamilySearch::class);
        
        // Test with legacy operator format
        $filters = [
            [
                'type' => 'province',
                'value' => $this->province->id,
                'operator' => 'equals'  // old format
            ]
        ];
        
        $component->set('tempFilters', $filters);
        $component->call('applyFilters');
        
        $families = $component->get('families');
        
        // Should return 5 families from آذربایجان شرقی
        $this->assertEquals(5, $families->total());
        
        foreach ($families as $family) {
            $this->assertEquals($this->province->id, $family->province_id);
        }
    }

    /** @test */
    public function it_combines_multiple_province_filters_with_and_logic()
    {
        Auth::login($this->user);
        
        // Create another test province
        $province2 = Province::create(['name' => 'اصفهان']);
        Family::factory()->count(2)->create([
            'province_id' => $province2->id,
            'status' => 'pending'
        ]);
        
        $component = Livewire::test(FamilySearch::class);
        
        // Test impossible combination: province = آذربایجان شرقی باشد AND province = اصفهان باشد
        $filters = [
            [
                'type' => 'province',
                'value' => $this->province->id,
                'existence_operator' => 'exists',
                'logical_operator' => 'and'
            ],
            [
                'type' => 'province', 
                'value' => $province2->id,
                'existence_operator' => 'exists',
                'logical_operator' => 'and'
            ]
        ];
        
        $component->set('tempFilters', $filters);
        $component->call('applyFilters');
        
        $families = $component->get('families');
        
        // Should return 0 families (impossible condition)
        $this->assertEquals(0, $families->total());
    }

    /** @test */
    public function it_combines_multiple_province_filters_with_or_logic()
    {
        Auth::login($this->user);
        
        // Create another test province
        $province2 = Province::create(['name' => 'اصفهان']);
        Family::factory()->count(2)->create([
            'province_id' => $province2->id,
            'status' => 'pending'
        ]);
        
        $component = Livewire::test(FamilySearch::class);
        
        // Test OR combination: province = آذربایجان شرقی باشد OR province = اصفهان باشد
        $filters = [
            [
                'type' => 'province',
                'value' => $this->province->id,
                'existence_operator' => 'exists',
                'logical_operator' => 'or'  // First one still gets treated as 'and' in current logic
            ],
            [
                'type' => 'province',
                'value' => $province2->id,
                'existence_operator' => 'exists',
                'logical_operator' => 'or'  // This should be OR
            ]
        ];
        
        $component->set('tempFilters', $filters);
        $component->call('applyFilters');
        
        $families = $component->get('families');
        
        // Should return 7 families (5 from آذربایجان شرقی + 2 from اصفهان)
        $this->assertEquals(7, $families->total());
    }

    /** @test */
    public function it_applies_province_filter_with_exists_any_province()
    {
        Auth::login($this->user);
        
        // Create a family with no province
        Family::factory()->create([
            'province_id' => null,
            'status' => 'pending'
        ]);
        
        $component = Livewire::test(FamilySearch::class);
        
        // Test general exists: any family with a province
        $filters = [
            [
                'type' => 'province',
                'value' => '', // Empty value means check for existence of any province
                'existence_operator' => 'exists',
                'logical_operator' => 'and'
            ]
        ];
        
        $component->set('tempFilters', $filters);
        $component->call('applyFilters');
        
        $families = $component->get('families');
        
        // Should return 8 families (all except the null province one)
        $this->assertEquals(8, $families->total());
        
        foreach ($families as $family) {
            $this->assertNotNull($family->province_id);
        }
    }

    /** @test */
    public function it_shows_debugging_info_for_failed_filter()
    {
        Auth::login($this->user);
        
        $component = Livewire::test(FamilySearch::class);
        
        // Test the exact case reported: province = آذربایجان شرقی باشد
        $filters = [
            [
                'type' => 'province',
                'value' => $this->province->id,
                'existence_operator' => 'exists',
                'logical_operator' => 'and'
            ]
        ];
        
        $component->set('tempFilters', $filters);
        
        // Debug: Check the internal state before applying
        $tempFilters = $component->get('tempFilters');
        $this->assertCount(1, $tempFilters);
        $this->assertEquals('province', $tempFilters[0]['type']);
        $this->assertEquals($this->province->id, $tempFilters[0]['value']);
        $this->assertEquals('exists', $tempFilters[0]['existence_operator']);
        $this->assertEquals('and', $tempFilters[0]['logical_operator']);
        
        $component->call('applyFilters');
        
        $activeFilters = $component->get('activeFilters');
        $this->assertCount(1, $activeFilters);
        
        $families = $component->get('families');
        
        // Add debug output
        if ($families->total() !== 5) {
            $actualCount = $families->total();
            $expectedCount = 5;
            $allFamilies = Family::all();
            $filteredFamilies = Family::where('province_id', $this->province->id)->get();
            
            $this->fail(
                "Province filter failed!\n" .
                "Expected: {$expectedCount} families\n" .
                "Actual: {$actualCount} families\n" .
                "Total families in DB: {$allFamilies->count()}\n" .
                "Families with province_id={$this->province->id}: {$filteredFamilies->count()}\n" .
                "Active filters: " . json_encode($activeFilters)
            );
        }
        
        $this->assertEquals(5, $families->total());
    }
}