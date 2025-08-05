<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Livewire\Insurance\DashboardStats;
use App\Models\User;
use App\Models\Family;
use App\Models\Member;
use App\Models\Organization;
use App\Models\FamilyInsurance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Livewire\Livewire;
use Mockery;

/**
 * تست واحد برای کامپوننت داشبورد آماری
 * تمرکز بر سناریوهای دیباگ و مدیریت خطا
 */
class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    private User $testUser;
    private Organization $testOrganization;

    protected function setUp(): void
    {
        parent::setUp();
        
        // ایجاد کاربر تست
        $this->testOrganization = Organization::factory()->create([
            'name' => 'Test Organization',
            'type' => 'insurance'
        ]);
        
        $this->testUser = User::factory()->create([
            'organization_id' => $this->testOrganization->id
        ]);
        
        $this->actingAs($this->testUser);
    }

    /**
     * تست بارگذاری موفق کامپوننت برای پنل بیمه
     * 
     * @test
     * @return void
     */
    public function test_insurance_panel_loads_successfully()
    {
        Log::shouldReceive('info')
            ->once()
            ->with(Mockery::pattern('/Starting dashboard statistics loading/'), Mockery::any())
            ->andReturn(true);
            
        Log::shouldReceive('info')
            ->once()
            ->with(Mockery::pattern('/Insurance statistics loaded successfully/'), Mockery::any())
            ->andReturn(true);

        $component = Livewire::test(DashboardStats::class, ['panelType' => 'insurance']);

        $component->assertSet('panelType', 'insurance')
                 ->assertSet('showFinancialData', true)
                 ->assertViewIs('livewire.insurance.dashboard-stats');
    }

    /**
     * تست بارگذاری موفق کامپوننت برای پنل خیریه
     * 
     * @test
     * @return void
     */
    public function test_charity_panel_loads_successfully()
    {
        // تغییر نوع سازمان به خیریه
        $this->testOrganization->update(['type' => 'charity']);
        
        Log::shouldReceive('info')
            ->once()
            ->with(Mockery::pattern('/Starting dashboard statistics loading/'), Mockery::any())
            ->andReturn(true);
            
        Log::shouldReceive('info')
            ->once()
            ->with(Mockery::pattern('/Charity statistics loaded successfully/'), Mockery::any())
            ->andReturn(true);

        $component = Livewire::test(DashboardStats::class, ['panelType' => 'charity']);

        $component->assertSet('panelType', 'charity')
                 ->assertSet('showFinancialData', false)
                 ->assertSet('insuredFamilies', 0)
                 ->assertSet('uninsuredFamilies', 0);
    }

    /**
     * تست مدیریت خطا در بارگذاری آمار
     * 
     * @test
     * @return void
     */
    public function test_handles_statistics_loading_error_gracefully()
    {
        // Mock Cache برای ایجاد خطا
        Cache::shouldReceive('remember')
            ->once()
            ->andThrow(new \Exception('Database connection failed', 500));

        // انتظار لاگ خطا
        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('error')
            ->once()
            ->with(Mockery::pattern('/Dashboard statistics loading failed/'), Mockery::any())
            ->andReturn(true);

        $component = Livewire::test(DashboardStats::class);

        // بررسی تنظیم مقادیر پیش‌فرض
        $component->assertSet('totalInsured', 0)
                 ->assertSet('totalPayment', 0)
                 ->assertSet('maleCount', 0)
                 ->assertSet('femaleCount', 0);

        // بررسی نمایش پیام خطا
        $this->assertTrue(session()->has('error'));
    }

    /**
     * تست تشخیص خودکار نوع پنل
     * 
     * @test
     * @return void
     */
    public function test_automatic_panel_type_detection()
    {
        // تست تشخیص پنل بیمه
        $this->testOrganization->update(['type' => 'insurance']);
        
        Log::shouldReceive('info')->andReturn(true);
        
        $component = Livewire::test(DashboardStats::class);
        $component->assertSet('panelType', 'insurance');

        // تست تشخیص پنل خیریه
        $this->testOrganization->update(['type' => 'charity']);
        
        $component = Livewire::test(DashboardStats::class);
        $component->assertSet('panelType', 'charity');
    }

    /**
     * تست عملکرد کش
     * 
     * @test
     * @return void
     */
    public function test_caching_functionality()
    {
        $cacheKey = 'insurance_dashboard_stats_1403__';
        $mockData = [
            'totalInsured' => 100,
            'maleCount' => 60,
            'femaleCount' => 40,
            'totalOrganizations' => 5,
            'totalPayment' => 50000
        ];

        // تست Cache Miss
        Cache::shouldReceive('remember')
            ->once()
            ->with($cacheKey, Mockery::any(), Mockery::any())
            ->andReturn($mockData);

        Log::shouldReceive('info')->andReturn(true);

        $component = Livewire::test(DashboardStats::class);

        $component->assertSet('totalInsured', 100)
                 ->assertSet('maleCount', 60)
                 ->assertSet('femaleCount', 40);
    }

    /**
     * تست فیلترهای داشبورد
     * 
     * @test
     * @return void
     */
    public function test_dashboard_filters_functionality()
    {
        Log::shouldReceive('info')->andReturn(true);

        $component = Livewire::test(DashboardStats::class);

        // تست تغییر سال
        $component->set('selectedYear', 1402)
                 ->assertSet('selectedYear', 1402);

        // تست تغییر ماه
        $component->set('selectedMonth', 6)
                 ->assertSet('selectedMonth', 6);

        // تست تغییر سازمان
        $component->set('selectedOrganization', $this->testOrganization->id)
                 ->assertSet('selectedOrganization', $this->testOrganization->id);

        // تست ری‌ست فیلترها
        $component->call('resetFilters')
                 ->assertSet('selectedMonth', null)
                 ->assertSet('selectedOrganization', null);
    }

    /**
     * تست پاک کردن کش
     * 
     * @test
     * @return void
     */
    public function test_cache_clearing_functionality()
    {
        Log::shouldReceive('info')->andReturn(true);
        
        Cache::shouldReceive('flush')
            ->once()
            ->andReturn(true);

        $component = Livewire::test(DashboardStats::class);
        
        $component->call('clearCache');
        
        // بررسی بازخوانی آمار پس از پاک کردن کش
        $component->assertSet('totalInsured', 0); // با فرض عدم وجود داده در تست
    }

    /**
     * تست رندر view با داده‌های مختلف
     * 
     * @test
     * @return void
     */
    public function test_view_rendering_with_different_data()
    {
        Log::shouldReceive('info')->andReturn(true);

        // تست رندر برای پنل بیمه
        $component = Livewire::test(DashboardStats::class, ['panelType' => 'insurance']);
        
        $component->assertViewIs('livewire.insurance.dashboard-stats')
                 ->assertViewHas('totalInsured')
                 ->assertViewHas('totalPayment')
                 ->assertViewHas('financialRatio');

        // تست رندر برای پنل خیریه
        $component = Livewire::test(DashboardStats::class, ['panelType' => 'charity']);
        
        $component->assertViewIs('livewire.insurance.dashboard-stats')
                 ->assertViewHas('totalInsured')
                 ->assertViewHas('criteriaData');
    }

    /**
     * تست مدیریت خطا در محاسبه آمار خیریه
     * 
     * @test  
     * @return void
     */
    public function test_charity_statistics_error_handling()
    {
        $this->testOrganization->update(['type' => 'charity']);
        
        // Mock برای ایجاد خطا در کوئری
        Family::shouldReceive('query')
            ->andThrow(new \Exception('Query execution failed'));

        Log::shouldReceive('info')->andReturn(true);
        Log::shouldReceive('error')
            ->once()
            ->with(Mockery::pattern('/Dashboard statistics loading failed/'), Mockery::any())
            ->andReturn(true);

        $component = Livewire::test(DashboardStats::class, ['panelType' => 'charity']);

        // بررسی مقادیر پیش‌فرض خیریه
        $component->assertSet('insuredFamilies', 0)
                 ->assertSet('uninsuredFamilies', 0)
                 ->assertSet('totalFamilies', 0)
                 ->assertSet('totalDeprived', 0);
    }

    /**
     * تست عملکرد با کاربر غیرمجاز
     * 
     * @test
     * @return void
     */
    public function test_unauthorized_user_handling()
    {
        Auth::logout();
        
        // تست بدون کاربر وارد شده
        $component = Livewire::test(DashboardStats::class);
        
        // کامپوننت باید با مقادیر پیش‌فرض کار کند
        $component->assertSet('panelType', 'insurance')
                 ->assertSet('totalInsured', 0);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
