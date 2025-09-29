<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Family;
use App\Models\Province;
use App\Models\User;
use App\Livewire\Charity\FamilySearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class FamilySearchFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $familySearchComponent;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'user_type' => 'admin'
        ]);
        
        Auth::login($this->user);
        
        $this->familySearchComponent = new FamilySearch();
        $this->familySearchComponent->mount();
    }

    public function test_applySingleFilter_province_exists_with_specific_value()
    {
        // Create test data
        $province = Province::create(['name' => 'آذربایجان شرقی']);
        $targetFamilies = Family::factory()->count(3)->create(['province_id' => $province->id]);
        $otherFamilies = Family::factory()->count(2)->create(['province_id' => Province::factory()->create()->id]);
        
        // Test filter configuration
        $filter = [
            'type' => 'province',
            'value' => $province->id,
            'existence_operator' => 'exists',
            'logical_operator' => 'and'
        ];
        
        // Test the applySingleFilter method
        $queryBuilder = Family::query();
        
        $reflection = new \ReflectionClass($this->familySearchComponent);
        $method = $reflection->getMethod('applySingleFilter');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->familySearchComponent, $queryBuilder, $filter, 'and');
        $count = $result->count();
        
        $this->assertEquals(3, $count, "Should return exactly 3 families from the target province");
        
        // Verify that returned families are from correct province
        $families = $result->get();
        foreach ($families as $family) {
            $this->assertEquals($province->id, $family->province_id);
        }
    }

    public function test_applySingleFilter_province_not_exists_with_specific_value()
    {
        // Create test data
        $province = Province::create(['name' => 'آذربایجان شرقی']);
        $targetFamilies = Family::factory()->count(3)->create(['province_id' => $province->id]);
        $otherProvince = Province::factory()->create(['name' => 'تهران']);
        $otherFamilies = Family::factory()->count(2)->create(['province_id' => $otherProvince->id]);
        
        // Test filter configuration - NOT from آذربایجان شرقی
        $filter = [
            'type' => 'province',
            'value' => $province->id,
            'existence_operator' => 'not_exists',
            'logical_operator' => 'and'
        ];
        
        // Test the applySingleFilter method
        $queryBuilder = Family::query();
        
        $reflection = new \ReflectionClass($this->familySearchComponent);
        $method = $reflection->getMethod('applySingleFilter');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->familySearchComponent, $queryBuilder, $filter, 'and');
        $count = $result->count();
        
        $this->assertEquals(2, $count, "Should return exactly 2 families NOT from the target province");
        
        // Verify that returned families are NOT from the target province
        $families = $result->get();
        foreach ($families as $family) {
            $this->assertNotEquals($province->id, $family->province_id);
        }
    }

    public function test_applySingleFilter_province_exists_empty_value()
    {
        // Create test data
        $province = Province::create(['name' => 'آذربایجان شرقی']);
        $familiesWithProvince = Family::factory()->count(4)->create(['province_id' => $province->id]);
        $familiesWithoutProvince = Family::factory()->count(2)->create(['province_id' => null]);
        
        // Test filter configuration - any province exists (empty value)
        $filter = [
            'type' => 'province',
            'value' => '', // Empty value
            'existence_operator' => 'exists',
            'logical_operator' => 'and'
        ];
        
        // Test the applySingleFilter method
        $queryBuilder = Family::query();
        
        $reflection = new \ReflectionClass($this->familySearchComponent);
        $method = $reflection->getMethod('applySingleFilter');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->familySearchComponent, $queryBuilder, $filter, 'and');
        $count = $result->count();
        
        $this->assertEquals(4, $count, "Should return families that have any province (not null)");
        
        // Verify that returned families have province_id
        $families = $result->get();
        foreach ($families as $family) {
            $this->assertNotNull($family->province_id);
        }
    }

    public function test_applySingleFilter_province_not_exists_empty_value()
    {
        // Create test data
        $province = Province::create(['name' => 'آذربایجان شرقی']);
        $familiesWithProvince = Family::factory()->count(3)->create(['province_id' => $province->id]);
        $familiesWithoutProvince = Family::factory()->count(2)->create(['province_id' => null]);
        
        // Test filter configuration - no province exists (empty value)
        $filter = [
            'type' => 'province',
            'value' => '', // Empty value
            'existence_operator' => 'not_exists',
            'logical_operator' => 'and'
        ];
        
        // Test the applySingleFilter method
        $queryBuilder = Family::query();
        
        $reflection = new \ReflectionClass($this->familySearchComponent);
        $method = $reflection->getMethod('applySingleFilter');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->familySearchComponent, $queryBuilder, $filter, 'and');
        $count = $result->count();
        
        $this->assertEquals(2, $count, "Should return families that have no province (null)");
        
        // Verify that returned families have null province_id
        $families = $result->get();
        foreach ($families as $family) {
            $this->assertNull($family->province_id);
        }
    }

    public function test_applySingleFilter_city_exists_with_specific_value()
    {
        // Create test data for city filtering
        $city = \App\Models\City::factory()->create(['name' => 'تبریز']);
        $targetFamilies = Family::factory()->count(3)->create(['city_id' => $city->id]);
        $otherFamilies = Family::factory()->count(2)->create(['city_id' => \App\Models\City::factory()->create()->id]);
        
        // Test filter configuration
        $filter = [
            'type' => 'city',
            'value' => $city->id,
            'existence_operator' => 'exists',
            'logical_operator' => 'and'
        ];
        
        // Test the applySingleFilter method
        $queryBuilder = Family::query();
        
        $reflection = new \ReflectionClass($this->familySearchComponent);
        $method = $reflection->getMethod('applySingleFilter');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->familySearchComponent, $queryBuilder, $filter, 'and');
        $count = $result->count();
        
        $this->assertEquals(3, $count, "Should return exactly 3 families from the target city");
        
        // Verify that returned families are from correct city
        $families = $result->get();
        foreach ($families as $family) {
            $this->assertEquals($city->id, $family->city_id);
        }
    }

    public function test_legacy_operator_compatibility()
    {
        // Test backward compatibility with old operator format
        $province = Province::create(['name' => 'آذربایجان شرقی']);
        $targetFamilies = Family::factory()->count(3)->create(['province_id' => $province->id]);
        
        // Test with legacy 'equals' operator
        $filter = [
            'type' => 'province',
            'value' => $province->id,
            'operator' => 'equals' // Old format
        ];
        
        // Test the applySingleFilter method
        $queryBuilder = Family::query();
        
        $reflection = new \ReflectionClass($this->familySearchComponent);
        $method = $reflection->getMethod('applySingleFilter');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->familySearchComponent, $queryBuilder, $filter, 'and');
        $count = $result->count();
        
        $this->assertEquals(3, $count, "Legacy operator format should still work");
    }

    public function test_convertModalFiltersToQueryBuilder_with_province_filter()
    {
        // Create test data
        $province = Province::create(['name' => 'آذربایجان شرقی']);
        $targetFamilies = Family::factory()->count(3)->create(['province_id' => $province->id]);
        $otherFamilies = Family::factory()->count(2)->create(['province_id' => Province::factory()->create()->id]);
        
        // Set up component with filters
        $this->familySearchComponent->activeFilters = [
            [
                'type' => 'province',
                'value' => $province->id,
                'existence_operator' => 'exists',
                'logical_operator' => 'and'
            ]
        ];
        
        $baseQuery = Family::query();
        
        $reflection = new \ReflectionClass($this->familySearchComponent);
        $method = $reflection->getMethod('convertModalFiltersToQueryBuilder');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->familySearchComponent, $baseQuery);
        $count = $result->count();
        
        $this->assertEquals(3, $count, "convertModalFiltersToQueryBuilder should apply province filter correctly");
    }

    public function test_multiple_filters_with_and_logic()
    {
        // Create test data
        $province = Province::create(['name' => 'آذربایجان شرقی']);
        $city = \App\Models\City::factory()->create(['name' => 'تبریز', 'province_id' => $province->id]);
        
        // Families matching both conditions
        $matchingFamilies = Family::factory()->count(2)->create([
            'province_id' => $province->id,
            'city_id' => $city->id
        ]);
        
        // Families matching only province
        $provinceOnlyFamilies = Family::factory()->count(1)->create([
            'province_id' => $province->id,
            'city_id' => \App\Models\City::factory()->create()->id
        ]);
        
        // Families matching only city (different province)
        $cityOnlyFamilies = Family::factory()->count(1)->create([
            'province_id' => Province::factory()->create()->id,
            'city_id' => $city->id
        ]);
        
        // Set up component with AND filters
        $this->familySearchComponent->activeFilters = [
            [
                'type' => 'province',
                'value' => $province->id,
                'existence_operator' => 'exists',
                'logical_operator' => 'and'
            ],
            [
                'type' => 'city',
                'value' => $city->id,
                'existence_operator' => 'exists',
                'logical_operator' => 'and'
            ]
        ];
        
        $baseQuery = Family::query();
        
        $reflection = new \ReflectionClass($this->familySearchComponent);
        $method = $reflection->getMethod('convertModalFiltersToQueryBuilder');
        $method->setAccessible(true);
        
        $result = $method->invoke($this->familySearchComponent, $baseQuery);
        $count = $result->count();
        
        $this->assertEquals(2, $count, "AND logic should return only families matching both province AND city");
        
        // Verify results
        $families = $result->get();
        foreach ($families as $family) {
            $this->assertEquals($province->id, $family->province_id);
            $this->assertEquals($city->id, $family->city_id);
        }
    }
}