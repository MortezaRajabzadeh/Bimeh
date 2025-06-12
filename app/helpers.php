<?php

if (!function_exists('persianNumbers')) {
    /**
     * تبدیل اعداد انگلیسی به فارسی
     *
     * @param int|string $number
     * @return string
     */
    function persianNumbers($number)
    {
        $farsi_array = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english_array = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        return str_replace($english_array, $farsi_array, (string)$number);
    }
} 
