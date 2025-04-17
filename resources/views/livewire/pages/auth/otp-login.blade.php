<div>
    <div class="container compact">
        <main class="content">
            <div class="logo-container">
                <div class="logo">
                    <div class="map-pin">
                        <div class="pin-outer"></div>
                        <div class="pin-inner"></div>
                    </div>
                </div>
                <h1 class="brand-name">پینوتو</h1>
                <h2 class="tagline">بیمه بدنه سفر محور خودرو</h2>
            </div>
            
            @if (session()->has('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            @if (session()->has('error'))
                <div class="alert alert-error">
                    {{ session('error') }}
                </div>
            @endif
            
            <div class="form-container">
                @if (!$showVerificationForm)
                    <!-- فرم ورود شماره موبایل -->
                    <form wire:submit="sendOtp">
                        <label for="mobile" class="form-label">شماره موبایل خود را وارد نمایید</label>
                        
                        <div style="position: relative; display: flex; align-items: center;">
                            <input 
                                wire:model="mobile" 
                                type="tel" 
                                id="mobile" 
                                class="phone-input" 
                                placeholder="09123456789" 
                                maxlength="11" 
                                pattern="[0-9]{11}" 
                                inputmode="numeric" 
                                dir="ltr"
                            >
                            @if (strlen($mobile) === 11 && preg_match('/^09\d{9}$/', $mobile))
                                <div class="check-icon" style="position: absolute; right: 10px;"></div>
                            @endif
                        </div>
                        
                        @error('mobile') 
                            <span class="error-message">{{ $message }}</span> 
                        @enderror
                        
                        <button type="submit" class="submit-button">دریافت کد تایید</button>
                    </form>

                    @if (app()->environment('local', 'development'))
                        <div class="alert alert-info" style="margin-top: 1rem; text-align: center; direction: ltr;">
                            Development OTP Code: {{ session('dev_otp_code') }}
                        </div>
                    @endif
                @else
                    <!-- فرم تایید کد -->
                    <form wire:submit="verifyOtp">
                        <label for="code" class="form-label">
                            کد تایید ارسال شده به شماره {{ substr($mobile, 0, 4) . '***' . substr($mobile, -4) }} را وارد کنید
                        </label>
                        
                        @if (app()->environment('local', 'development'))
                            <div class="alert alert-info" style="margin-bottom: 1rem; text-align: center; direction: ltr; background: #e3f2fd; padding: 10px; border-radius: 8px; color: #0d47a1;">
                                Development OTP Code: <strong>{{ session('dev_otp_code') }}</strong>
                            </div>
                        @endif

                        <div class="otp-inputs" x-data="{ 
                            otpCode: @entangle('otpCode'),
                            focusNext(index) {
                                if (index < 5) {
                                    this.$refs[`otp_${index + 1}`].focus();
                                }
                            },
                            focusPrev(index) {
                                if (index > 0) {
                                    this.$refs[`otp_${index - 1}`].focus();
                                }
                            },
                            handleInput(e, index) {
                                const input = e.target;
                                const value = input.value;
                                
                                if (value.length === 1 && /^[0-9]$/.test(value)) {
                                    this.focusNext(index);
                                }
                                
                                // تجمیع کدها
                                let code = '';
                                for (let i = 0; i < 6; i++) {
                                    code += this.$refs[`otp_${i}`].value;
                                }
                                this.otpCode = code;
                            },
                            handleKeydown(e, index) {
                                if (e.key === 'Backspace' && e.target.value === '') {
                                    this.focusPrev(index);
                                }
                            },
                            handlePaste(e) {
                                e.preventDefault();
                                const paste = (e.clipboardData || window.clipboardData).getData('text');
                                const digits = paste.match(/\d/g);
                                
                                if (digits && digits.length === 6) {
                                    for (let i = 0; i < 6; i++) {
                                        this.$refs[`otp_${i}`].value = digits[i];
                                    }
                                    this.otpCode = paste;
                                }
                            }
                        }">
                            @for ($i = 0; $i < 6; $i++)
                                <input
                                    type="text"
                                    maxlength="1"
                                    class="otp-input"
                                    x-ref="otp_{{ $i }}"
                                    @input="handleInput($event, {{ $i }})"
                                    @keydown="handleKeydown($event, {{ $i }})"
                                    @paste="handlePaste"
                                    inputmode="numeric"
                                    pattern="[0-9]"
                                    style="width: 45px; height: 45px; margin: 0 4px; text-align: center; font-size: 18px; border: 1px solid #ddd; border-radius: 8px;"
                                >
                            @endfor
                        </div>
                        
                        @error('otpCode') 
                            <span class="error-message">{{ $message }}</span> 
                        @enderror
                        
                        <div class="button-group">
                            <button type="button" wire:click="backToMobile" class="back-button">
                                بازگشت
                            </button>
                            
                            <button type="submit" class="submit-button">
                                ورود
                            </button>
                        </div>
                        
                        @if ($canResend)
                            <button type="button" wire:click="sendOtp" class="resend-button">
                                ارسال مجدد کد
                            </button>
                        @elseif ($resendTimerCount > 0)
                            <div class="resend-timer" 
                                x-data="{ 
                                    timer: $wire.resendTimerCount,
                                    startTimer() {
                                        let interval = setInterval(() => {
                                            this.timer--;
                                            if (this.timer <= 0) {
                                                clearInterval(interval);
                                                $wire.enableResend();
                                            }
                                        }, 1000);
                                    }
                                }" 
                                x-init="startTimer()"
                            >
                                ارسال مجدد کد تا <span x-text="timer">{{ $resendTimerCount }}</span> ثانیه دیگر
                            </div>
                        @endif
                    </form>
                @endif
            </div>
        </main>
    </div>
    
    <link rel="stylesheet" href="{{ asset('css/pinoto-style.css') }}">
    <script src="{{ asset('js/pinoto-script.js') }}"></script>
</div> 