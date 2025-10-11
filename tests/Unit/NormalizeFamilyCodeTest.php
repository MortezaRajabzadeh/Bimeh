<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\InsuranceShareService;
use Illuminate\Support\Facades\Log;

class NormalizeFamilyCodeTest extends TestCase
{
    private InsuranceShareService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InsuranceShareService();
    }

    /** @test */
    public function it_preserves_17_digit_family_codes()
    {
        // استفاده از Reflection برای دسترسی به متد private
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeFamilyCode');
        $method->setAccessible(true);

        // تست کد 17 رقمی
        $longCode = '20250929012138721';
        $result = $method->invoke($this->service, $longCode);
        
        $this->assertEquals($longCode, $result, 'کد خانوار 17 رقمی باید حفظ شود');
        $this->assertEquals(17, strlen($result), 'طول کد خانوار باید 17 رقم باشد');
    }

    /** @test */
    public function it_removes_decimal_zeros_without_losing_precision()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeFamilyCode');
        $method->setAccessible(true);

        // تست کد با .0 در انتها
        $codeWithDecimal = '20250929012138721.0';
        $result = $method->invoke($this->service, $codeWithDecimal);
        
        $this->assertEquals('20250929012138721', $result);
        $this->assertEquals(17, strlen($result));
    }

    /** @test */
    public function it_handles_string_numbers_correctly()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeFamilyCode');
        $method->setAccessible(true);

        $testCases = [
            '20250929012138721' => '20250929012138721',  // رشته عددی
            '12345678901234567' => '12345678901234567',  // 17 رقم
            '1234567890123456' => '1234567890123456',    // 16 رقم
            '123456789012345' => '123456789012345',      // 15 رقم
        ];

        foreach ($testCases as $input => $expected) {
            $result = $method->invoke($this->service, $input);
            $this->assertEquals($expected, $result, "Input: {$input} should return {$expected}");
        }
    }

    /** @test */
    public function it_handles_multiple_decimal_zeros()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeFamilyCode');
        $method->setAccessible(true);

        $testCases = [
            '20250929012138721.0' => '20250929012138721',
            '20250929012138721.00' => '20250929012138721',
            '20250929012138721.000' => '20250929012138721',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $method->invoke($this->service, $input);
            $this->assertEquals($expected, $result, "Input: {$input}");
            $this->assertEquals(17, strlen($result));
        }
    }

    /** @test */
    public function it_handles_empty_values()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeFamilyCode');
        $method->setAccessible(true);

        $this->assertEquals('', $method->invoke($this->service, ''));
        $this->assertEquals('', $method->invoke($this->service, null));
        $this->assertEquals('', $method->invoke($this->service, 0));
    }

    /** @test */
    public function it_trims_whitespace()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeFamilyCode');
        $method->setAccessible(true);

        $codeWithSpaces = '  20250929012138721  ';
        $result = $method->invoke($this->service, $codeWithSpaces);
        
        $this->assertEquals('20250929012138721', $result);
    }
}
