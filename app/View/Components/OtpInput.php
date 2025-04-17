<?php

namespace App\View\Components;

use Illuminate\View\Component;

class OtpInput extends Component
{
    /**
     * تعداد ارقام کد OTP
     */
    public int $digits;

    /**
     * آیا ارسال خودکار فعال باشد؟
     */
    public bool $autoSubmit;

    /**
     * شناسه فیلد مخفی برای نگهداری کد کامل
     */
    public string $hiddenInputId;

    /**
     * Create a new component instance.
     */
    public function __construct(
        int $digits = 6,
        bool $autoSubmit = true,
        string $hiddenInputId = 'code'
    ) {
        $this->digits = $digits;
        $this->autoSubmit = $autoSubmit;
        $this->hiddenInputId = $hiddenInputId;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.otp-input');
    }
}
