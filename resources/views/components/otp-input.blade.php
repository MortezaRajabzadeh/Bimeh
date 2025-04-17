@props([
    'wireModel' => null,
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'otp-input-container ' . $class]) }}>
    <!-- فیلد مخفی برای نگهداری کد کامل -->
    <input 
        type="hidden" 
        id="{{ $hiddenInputId }}"
        @if($wireModel) wire:model.live="{{ $wireModel }}" @endif
    >
    
    <!-- فیلدهای جداگانه برای ورود کد -->
    <div class="otp-inputs" wire:ignore>
        @for($i = 0; $i < $digits; $i++)
            <input 
                type="text" 
                maxlength="1" 
                inputmode="numeric"
                class="otp-digit"
                data-auto-submit="{{ $autoSubmit }}"
                aria-label="رقم {{ $i + 1 }} از {{ $digits }}"
                autocomplete="off"
            >
        @endfor
    </div>
</div>

@once
    @push('styles')
    <style>
        .otp-input-container {
            width: 100%;
            max-width: 360px;
            margin: 0 auto;
        }
        
        .otp-inputs {
            display: flex;
            gap: 8px;
            direction: ltr;
        }
        
        .otp-digit {
            width: 40px;
            height: 48px;
            border: 2px solid #ddd;
            border-radius: 8px;
            text-align: center;
            font-size: 20px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .otp-digit:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 2px rgba(79, 70, 229, 0.1);
            outline: none;
        }
        
        .otp-digit.filled {
            border-color: #10b981;
            background-color: #f0fdf4;
        }
        
        .otp-digit.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }
        
        @media (max-width: 480px) {
            .otp-digit {
                width: 36px;
                height: 42px;
                font-size: 18px;
            }
        }
    </style>
    @endpush
@endonce