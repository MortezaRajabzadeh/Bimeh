<?php

namespace Tests\Feature;

use App\Events\Auth\OtpLoginSuccessful;
use App\Livewire\Auth\OtpLogin;
use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock SMS service
        $this->mock(SmsService::class)
            ->shouldReceive('sendOtp')
            ->andReturn(true);
    }

    /** @test */
    public function it_shows_otp_form_after_valid_mobile_input()
    {
        Livewire::test(OtpLogin::class)
            ->set('mobile', '09123456789')
            ->call('sendOtp')
            ->assertSet('showVerificationForm', true)
            ->assertSet('resendTimerCount', 120)
            ->assertDispatched('otp-sent');
    }

    /** @test */
    public function it_validates_mobile_number()
    {
        Livewire::test(OtpLogin::class)
            ->set('mobile', '0912345') // invalid mobile
            ->call('sendOtp')
            ->assertHasErrors(['mobile'])
            ->assertSet('showVerificationForm', false);
    }

    /** @test */
    public function it_enforces_rate_limiting_on_otp_requests()
    {
        $mobile = '09123456789';
        
        // Simulate hitting rate limit
        for ($i = 0; $i < OtpService::HOURLY_ATTEMPTS; $i++) {
            Livewire::test(OtpLogin::class)
                ->set('mobile', $mobile)
                ->call('sendOtp');
        }
        
        // Next attempt should fail
        Livewire::test(OtpLogin::class)
            ->set('mobile', $mobile)
            ->call('sendOtp')
            ->assertHasErrors('mobile');
    }

    /** @test */
    public function it_can_verify_valid_otp_and_login_user()
    {
        Event::fake();
        
        $mobile = '09123456789';
        $code = '123456';
        
        // Store OTP in cache
        Cache::put("otp_code_{$mobile}", $code, now()->addMinutes(5));
        
        Livewire::test(OtpLogin::class)
            ->set('mobile', $mobile)
            ->set('otpCode', $code)
            ->call('verifyOtp')
            ->assertRedirect(route('dashboard'));
        
        // Assert user was created and logged in
        $this->assertAuthenticated();
        
        // Assert event was dispatched
        Event::assertDispatched(OtpLoginSuccessful::class, function ($event) use ($mobile) {
            return $event->mobile === $mobile;
        });
    }

    /** @test */
    public function it_handles_invalid_otp_attempts()
    {
        $mobile = '09123456789';
        
        Livewire::test(OtpLogin::class)
            ->set('mobile', $mobile)
            ->set('otpCode', '111111') // wrong code
            ->call('verifyOtp')
            ->assertHasErrors('otpCode');
        
        $this->assertGuest();
    }

    /** @test */
    public function it_blocks_after_too_many_invalid_attempts()
    {
        $mobile = '09123456789';
        $component = Livewire::test(OtpLogin::class)
            ->set('mobile', $mobile);
        
        // Simulate multiple failed attempts
        for ($i = 0; $i <= OtpService::VERIFY_ATTEMPTS; $i++) {
            $component->set('otpCode', '111111')
                ->call('verifyOtp');
        }
        
        // Next attempt should be blocked
        $component->set('otpCode', '222222')
            ->call('verifyOtp')
            ->assertHasErrors('otpCode');
        
        $this->assertGuest();
    }

    /** @test */
    public function it_can_resend_otp_after_timer_expires()
    {
        $component = Livewire::test(OtpLogin::class)
            ->set('mobile', '09123456789')
            ->call('sendOtp');
        
        // Simulate timer expiry
        $component->set('resendTimerCount', 0)
            ->call('enableResend')
            ->assertSet('canResend', true)
            ->assertSet('resendTimerCount', null);
        
        // Should be able to resend
        $component->call('sendOtp')
            ->assertSet('resendTimerCount', 120)
            ->assertSet('canResend', false);
    }
}
