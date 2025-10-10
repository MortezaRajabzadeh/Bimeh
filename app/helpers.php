<?php

use App\Helpers\ProblemTypeHelper;

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

if (!function_exists('problem_type_to_persian')) {
    /**
     * تبدیل نوع مشکل به فارسی
     */
    function problem_type_to_persian(string $english): string
    {
        return ProblemTypeHelper::englishToPersian($english);
    }
}

if (!function_exists('problem_type_to_english')) {
    /**
     * تبدیل نوع مشکل به انگلیسی
     */
    function problem_type_to_english(string $persian): string
    {
        return ProblemTypeHelper::persianToEnglish($persian);
    }
}

if (!function_exists('get_persian_problem_types')) {
    /**
     * دریافت تمام انواع مشکلات به فارسی
     */
    function get_persian_problem_types(): array
    {
        return ProblemTypeHelper::getPersianValues();
    }
}

if (!function_exists('is_valid_problem_type')) {
    /**
     * بررسی معتبر بودن نوع مشکل
     */
    function is_valid_problem_type(string $value): bool
    {
        return ProblemTypeHelper::isValidValue($value);
    }
} 

if (!function_exists('format_currency')) {
    /**
     * فرمت کردن مبلغ با جداکننده فارسی و واحد تومان
     *
     * @param int|float|string $amount
     * @return string
     */
    function format_currency($amount)
    {
        if (!$amount || $amount == 0) {
            return '۰ تومان';
        }

        // فرمت‌بندی با جداکننده فارسی
        $formatted = number_format($amount, 0, '.', '٬');

        return $formatted . ' تومان';
    }
}
