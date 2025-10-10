<?php

namespace App\Helpers;

class ProblemTypeHelper
{
    /**
     * تبدیل مقادیر انگلیسی به فارسی - کامل و بهبود یافته
     */
    public static function englishToPersian(string $english): string
    {
        $allTypes = self::getAllProblemTypes();
        
        $mapping = $allTypes + [
            // مترادفات و مقادیر جایگزین
            'special_diseases' => 'بیماری خاص',
            'chronic_diseases' => 'بیماری مزمن',
            'jobless' => 'بیکاری',
            'unemployed' => 'بیکاری',
            'school_dropout' => 'ترک تحصیل',
            'elderly' => 'کهولت سن',
            
            // مقادیر فارسی به فارسی (نرمالیزه سازی)
            'ترک تحصیل' => 'ترک تحصیل',
            'بیماری خاص' => 'بیماری خاص',
            'بیماری های خاص' => 'بیماری خاص',
            'سرپرست خانوار' => 'زن سرپرست خانواده',
            'سرپرست خانوار زن' => 'زن سرپرست خانواده',
            'زن سرپرست خانوار' => 'زن سرپرست خانواده',
            'سالمندی' => 'کهولت سن'
        ];

        return $mapping[trim($english)] ?? $english;
    }

    /**
     * تبدیل مقادیر فارسی به انگلیسی - کامل و بهبود یافته
     */
    public static function persianToEnglish(string $persian): string
    {
        $allTypes = array_flip(self::getAllProblemTypes());
        
        $mapping = $allTypes + [
            // مترادفات و مقادیر جایگزین
            'بیماری های خاص' => 'special_disease',
            'بیماریهای خاص' => 'special_disease',
            'بیماریهایخاص' => 'special_disease',
            'سرپرست خانوار' => 'female_head',
            'زن سرپرست خانوار' => 'female_head',
            'سرپرست خانوار زن' => 'female_head',
            'زن سرپرست خانواده' => 'female_head',
            'سالمندی' => 'old_age',
            'ازکارافتادگی' => 'work_disability'
        ];

        return $mapping[trim($persian)] ?? $persian;
    }

    /**
     * دریافت تمام مقادیر فارسی
     */
    public static function getPersianValues(): array
    {
        return [
            'اعتیاد',
            'بیماری خاص',
            'از کار افتادگی',
            'بیکاری',
            'زن سرپرست خانواده',
        ];
    }

    /**
     * دریافت تمام مقادیر انگلیسی
     */
    public static function getEnglishValues(): array
    {
        return [
            'addiction',
            'special_disease',
            'work_disability',
            'unemployment',
            'female_head'
        ];
    }

    /**
     * تبدیل آرایه‌ای از مقادیر
     */
    public static function convertArray(array $values, string $direction = 'to_persian'): array
    {
        $converted = [];
        
        foreach ($values as $value) {
            if ($direction === 'to_persian') {
                $converted[] = self::englishToPersian($value);
            } else {
                $converted[] = self::persianToEnglish($value);
            }
        }
        
        return $converted;
    }

    /**
     * تبدیل آرایه معیارها به فارسی برای نمایش
     * @param array $problemTypes
     * @return array
     */
    public static function convertArrayToPersian(array $problemTypes): array
    {
        $converted = [];
        
        foreach ($problemTypes as $problemType) {
            if (!empty($problemType)) {
                $converted[] = self::englishToPersian(trim($problemType));
            }
        }
        
        // حذف تکراری و مرتب‌سازی
        return array_unique(array_values($converted));
    }

    /**
     * تبدیل رشته معیارها (با کاما جدا شده) به فارسی
     * @param string $problemTypesString
     * @return string
     */
    public static function convertStringToPersian(string $problemTypesString): string
    {
        if (empty(trim($problemTypesString))) {
            return '';
        }
        
        $problemTypes = array_map('trim', explode(',', $problemTypesString));
        $converted = self::convertArrayToPersian($problemTypes);
        
        return implode(', ', $converted);
    }

    /**
     * دریافت تمام انواع معیارها به صورت آرایه key => value (انگلیسی => فارسی)
     */
    public static function getAllProblemTypes(): array
    {
        return [
            'addiction' => 'اعتیاد',
            'special_disease' => 'بیماری خاص',
            'work_disability' => 'از کار افتادگی',
            'unemployment' => 'بیکاری',
            'female_head' => 'زن سرپرست خانواده'
        ];
    }

    /**
     * بررسی اینکه آیا مقدار معتبر است
     */
    public static function isValidValue(string $value): bool
    {
        $allValues = array_merge(
            self::getPersianValues(),
            self::getEnglishValues(),
            array_keys(self::getAllProblemTypes()),
            array_values(self::getAllProblemTypes())
        );
        
        return in_array($value, $allValues);
    }
}
