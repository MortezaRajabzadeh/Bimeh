@props([
    'name',
    'type' => 'text',
    'placeholder' => '',
    'required' => false,
    'value' => '',
    'class' => '',
    'pattern' => null,
    'min' => null,
    'max' => null,
])

@php
    $persianMessages = [
        'mobile' => 'لطفاً شماره موبایل را وارد کنید.',
        'email' => 'لطفاً آدرس ایمیل را وارد کنید.',
        'password' => 'لطفاً رمز عبور را وارد کنید.',
        'name' => 'لطفاً نام را وارد کنید.',
        'first_name' => 'لطفاً نام را وارد کنید.',
        'last_name' => 'لطفاً نام خانوادگی را وارد کنید.',
        'username' => 'لطفاً نام کاربری را وارد کنید.',
        'national_code' => 'لطفاً کد ملی را وارد کنید.',
        'address' => 'لطفاً آدرس را وارد کنید.',
        'phone' => 'لطفاً شماره تلفن را وارد کنید.',
        'default' => 'لطفاً این فیلد را تکمیل کنید.'
    ];
    
    $customMessage = $persianMessages[$name] ?? $persianMessages['default'];
    $inputId = 'input_' . $name . '_' . uniqid();
@endphp

<input 
    id="{{ $inputId }}"
    name="{{ $name }}"
    type="{{ $type }}"
    value="{{ old($name, $value) }}"
    placeholder="{{ $placeholder }}"
    @if($required) required @endif
    @if($pattern) pattern="{{ $pattern }}" @endif
    @if($min) min="{{ $min }}" @endif
    @if($max) max="{{ $max }}" @endif
    class="{{ $class }}"
    data-persian-message="{{ $customMessage }}"
    {{ $attributes }}
/>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('{{ $inputId }}');
        const persianMessage = input.dataset.persianMessage;
        
        input.addEventListener('invalid', function(e) {
            e.target.setCustomValidity(persianMessage);
        });
        
        input.addEventListener('input', function(e) {
            e.target.setCustomValidity('');
        });
    });
</script> 