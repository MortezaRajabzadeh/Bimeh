// اسکریپت اصلی اپلیکیشن

document.addEventListener('DOMContentLoaded', function() {
    console.log('Pinoto script loaded!');
    setupMobileForm();
    
    // اجرای کد فقط اگر فرم OTP نمایش داده شده است
    if (document.querySelector('.otp-inputs')) {
        setupOtpInputs();
        setupResendTimer();
    }
});

// گوش دادن به رویدادهای livewire
document.addEventListener('livewire:navigated', function() {
    setupMobileForm();
    if (document.querySelector('.otp-inputs')) {
        setupOtpInputs();
        setupResendTimer();
    }
});

// تنظیم فرم موبایل
function setupMobileForm() {
    const mobileInput = document.querySelector('input[type="tel"]');
    const checkIcon = document.querySelector('.check-icon');
    
    if (!mobileInput) return;
    
    mobileInput.addEventListener('input', function() {
        const isValid = this.value.length === 11 && /^09\d{9}$/.test(this.value);
        if (checkIcon) {
            checkIcon.style.display = isValid ? 'block' : 'none';
        }
    });
}

// راه‌اندازی تایمر ارسال مجدد کد در سمت کلاینت
function setupResendTimer() {
    const timerElement = document.getElementById('resendTimer');
    if (!timerElement) return;
    
    let remainingTime = parseInt(timerElement.textContent, 10);
    if (isNaN(remainingTime) || remainingTime <= 0) return;
    
    // توقف تایمرهای قبلی
    if (window.resendCountdownInterval) {
        clearInterval(window.resendCountdownInterval);
    }
    
    // شروع تایمر جدید
    window.resendCountdownInterval = setInterval(() => {
        remainingTime -= 1;
        timerElement.textContent = remainingTime;
        
        if (remainingTime <= 0) {
            clearInterval(window.resendCountdownInterval);
            
            // اطلاع به کامپوننت Livewire که زمان به پایان رسیده
            const livewireEl = document.querySelector('[wire\\:id]');
            if (livewireEl && window.Livewire) {
                window.Livewire.find(livewireEl.getAttribute('wire:id')).call('enableResend');
            }
        }
    }, 1000);
    
    // پاکسازی تایمر هنگام خروج از صفحه
    window.addEventListener('beforeunload', () => {
        if (window.resendCountdownInterval) {
            clearInterval(window.resendCountdownInterval);
        }
    });
}

// راه‌اندازی فیلدهای OTP
function setupOtpInputs() {
    const inputs = document.querySelectorAll('.otp-digit');
    const hiddenInput = document.getElementById('code');
    const form = document.querySelector('[wire\\:id]');

    console.log('🔍 Setup started:', {
        inputsFound: inputs.length,
        hasHiddenInput: !!hiddenInput,
        formFound: !!form,
        formId: form?.getAttribute('wire:id')
    });

    if (!inputs.length || !hiddenInput || !form) {
        console.error('❌ Setup failed: Missing required elements');
        return;
    }

    function focusNext(currentIndex) {
        console.log('👉 Moving focus forward:', { from: currentIndex, to: currentIndex + 1 });
        if (currentIndex < inputs.length - 1) {
            inputs[currentIndex + 1].focus();
            inputs[currentIndex + 1].select();
        }
    }

    function focusPrev(currentIndex) {
        console.log('👈 Moving focus backward:', { from: currentIndex, to: currentIndex - 1 });
        if (currentIndex > 0) {
            inputs[currentIndex - 1].focus();
            inputs[currentIndex - 1].select();
        }
    }

    function handleInput(e) {
        const input = e.target;
        const index = Array.from(inputs).indexOf(input);
        const originalValue = input.value;
        
        // فقط اعداد را قبول کن
        let value = input.value.replace(/\D/g, '');
        
        console.log('📝 Input event:', {
            index,
            originalValue,
            cleanedValue: value,
            keyPressed: e.data
        });
        
        // محدود کردن به یک رقم
        if (value.length > 0) {
            input.value = value[value.length - 1];
            focusNext(index);
        }

        updateCode();
    }

    function handleKeydown(e) {
        const input = e.target;
        const index = Array.from(inputs).indexOf(input);

        console.log('⌨️ Keydown event:', {
            key: e.key,
            index,
            currentValue: input.value
        });

        if (e.key === 'Backspace') {
            e.preventDefault();
            if (input.value) {
                input.value = '';
            } else {
                focusPrev(index);
            }
            updateCode();
        } else if (e.key === 'ArrowLeft') {
            e.preventDefault();
            focusPrev(index);
        } else if (e.key === 'ArrowRight') {
            e.preventDefault();
            focusNext(index);
        }
    }

    function handlePaste(e) {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, inputs.length);
        
        console.log('📋 Paste event:', {
            originalData: e.clipboardData.getData('text'),
            cleanedData: pastedData,
            length: pastedData.length
        });
        
        if (pastedData) {
            [...pastedData].forEach((digit, i) => {
                if (i < inputs.length) {
                    inputs[i].value = digit;
                }
            });
            
            if (pastedData.length === inputs.length) {
                inputs[inputs.length - 1].focus();
            } else {
                inputs[Math.min(pastedData.length, inputs.length - 1)].focus();
            }
            
            updateCode();
        }
    }

    function updateCode() {
        const code = Array.from(inputs).map(input => input.value).join('');
        hiddenInput.value = code;

        console.log('🔄 Updating code:', {
            code,
            hiddenInputValue: hiddenInput.value,
            isComplete: code.length === inputs.length && !code.includes('')
        });

        // به‌روزرسانی مقدار در کامپوننت لایوایر
        if (window.Livewire) {
            try {
                const component = window.Livewire.find(form.getAttribute('wire:id'));
                console.log('⚡ Livewire update:', {
                    componentFound: !!component,
                    wireId: form.getAttribute('wire:id'),
                    code
                });
                
                component.set('otpCode', code);
                
                // اگر کد کامل است، فرم را ارسال کن
                if (code.length === inputs.length && !code.includes('')) {
                    console.log('🚀 Triggering verification...');
                    setTimeout(() => {
                        component.call('verifyOtp');
                    }, 100);
                }
            } catch (error) {
                console.error('❌ Livewire error:', error);
            }
        } else {
            console.warn('⚠️ Livewire not initialized');
        }
    }

    // پاکسازی و اضافه کردن event listeners
    inputs.forEach((input, index) => {
        input.value = '';
        input.setAttribute('autocomplete', 'off');
        
        input.addEventListener('input', handleInput);
        input.addEventListener('keydown', handleKeydown);
        input.addEventListener('paste', handlePaste);
        
        // جلوگیری از تایپ حروف
        input.addEventListener('keypress', (e) => {
            if (!/^\d$/.test(e.key)) {
                console.log('🚫 Invalid character blocked:', e.key);
                e.preventDefault();
            }
        });
    });

    // فوکوس روی اولین اینپوت
    setTimeout(() => {
        console.log('🎯 Setting initial focus');
        inputs[0].focus();
        inputs[0].select();
    }, 100);
}

// لیوایر
if (typeof Livewire !== 'undefined') {
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('redirect', (url) => {
            console.log('هدایت به:', url);
            setTimeout(() => {
                window.location.href = url;
            }, 200);
        });
    });
} 