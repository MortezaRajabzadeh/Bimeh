<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Family;
use App\Models\Province;
use App\Models\User;
use App\Livewire\Charity\FamilySearch;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

class SimpleProvinceFilterTest extends TestCase
{
    public function test_province_filter_with_real_data()
    {
        // Use existing data from the database
        $province = Province::where('name', 'آذربایجان شرقی')->first();
        
        if (!$province) {
            $this->markTestSkipped('آذربایجان شرقی province not found in database');
            return;
        }
        
        // Check if we have families from this province
        $familyCount = Family::where('province_id', $province->id)->count();
        
        if ($familyCount === 0) {
            $this->markTestSkipped('No families found for آذربایجان شرقی province');
            return;
        }
        
        // Create or get a user for authentication
        $user = User::first();
        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'user_type' => 'admin'
            ]);
        }
        
        Auth::login($user);
        
        // Test the FamilySearch component directly
        $component = Livewire::test(FamilySearch::class);
        
        // Test with single filter: province = آذربایجان شرقی باشد
        $filters = [
            [
                'type' => 'province',
                'value' => $province->id,
                'existence_operator' => 'exists', // باشد
                'logical_operator' => 'and'
            ]
        ];
        
        $component->set('tempFilters', $filters);
        $component->call('applyFilters');
        
        $activeFilters = $component->get('activeFilters');
        $this->assertCount(1, $activeFilters);
        
        $families = $component->get('families');
        
        // Debug output
        echo "\n=== DEBUG INFO ===\n";
        echo "Province ID: " . $province->id . "\n";
        echo "Province Name: " . $province->name . "\n";
        echo "Expected families count: " . $familyCount . "\n";
        echo "Actual families count: " . $families->total() . "\n";
        echo "Active filters: " . json_encode($activeFilters) . "\n";
        
        // Check if we got the expected number of families
        $this->assertEquals($familyCount, $families->total(), 
            "Expected $familyCount families from آذربایجان شرقی, got " . $families->total());
        
        // Verify all returned families are from the correct province
        foreach ($families as $family) {
            $this->assertEquals($province->id, $family->province_id,
                "Family {$family->id} has wrong province_id: {$family->province_id}, expected: {$province->id}");
        }
    }
    
    public function test_direct_query_builder_logic()
    {
        $province = Province::where('name', 'آذربایجان شرقی')->first();
        
        if (!$province) {
            $this->markTestSkipped('آذربایجان شرقی province not found in database');
            return;
        }
        
        // Test the direct database query
        $directQuery = Family::where('province_id', $province->id)->count();
        
        // Test using the same logic as applySingleFilter method
        $queryBuilder = Family::query();
        
        // Simulate the logic from applySingleFilter for province exists
        $queryBuilder = $queryBuilder->where('families.province_id', $province->id);
        
        $filteredCount = $queryBuilder->count();
        
        echo "\n=== QUERY BUILDER DEBUG ===\n";
        echo "Direct query count: " . $directQuery . "\n";
        echo "Filtered query count: " . $filteredCount . "\n";
        echo "SQL: " . $queryBuilder->toSql() . "\n";
        echo "Bindings: " . json_encode($queryBuilder->getBindings()) . "\n";
        
        $this->assertEquals($directQuery, $filteredCount,
            "Direct query and filtered query should return same count");
    }
    
    public function test_component_filter_conversion()
    {
        $province = Province::where('name', 'آذربایجان شرقی')->first();
        
        if (!$province) {
            $this->markTestSkipped('آذربایجان شرقی province not found in database');
            return;
        }
        
        $user = User::first() ?: User::create([
            'name' => 'Test User',
            'email' => 'test@example.com', 
            'password' => bcrypt('password'),
            'user_type' => 'admin'
        ]);
        
        Auth::login($user);
        
        $component = Livewire::test(FamilySearch::class);
        
        // Set the filter but don't apply yet
        $filters = [
            [
                'type' => 'province',
                'value' => $province->id,
                'existence_operator' => 'exists',
                'logical_operator' => 'and'
            ]
        ];
        
        $component->set('tempFilters', $filters);
        
        // Get the component instance to test the internal methods
        $componentInstance = $component->instance();
        
        // Test the convertModalFiltersToQueryBuilder method directly
        $baseQuery = Family::query();
        $componentInstance->activeFilters = $filters;
        
        // Use reflection to call the protected method
        $reflection = new \ReflectionClass($componentInstance);
        $method = $reflection->getMethod('convertModalFiltersToQueryBuilder');
        $method->setAccessible(true);
        
        $resultQuery = $method->invoke($componentInstance, $baseQuery);
        $count = $resultQuery->count();
        
        echo "\n=== FILTER CONVERSION DEBUG ===\n";
        echo "Filter conversion result count: " . $count . "\n";
        echo "Expected count: " . Family::where('province_id', $province->id)->count() . "\n";
        
        $this->assertGreaterThan(0, $count, "Filter conversion should return some results");
    }
}