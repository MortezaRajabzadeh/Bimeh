<?php

namespace App\Helpers;

class ProblemTypeHelper
{
    /**
     * تبدیل مقادیر انگلیسی به فارسی
     */
    public static function englishToPersian(string $english): string
    {
        $mapping = [
            'addiction' => 'اعتیاد',
            'special_disease' => 'بیماری های خاص',
            'work_disability' => 'از کار افتادگی',
            'unemployment' => 'بیکاری',
        ];

        return $mapping[$english] ?? $english;
    }

    /**
     * تبدیل مقادیر فارسی به انگلیسی
     */
    public static function persianToEnglish(string $persian): string
    {
        $mapping = [
            'اعتیاد' => 'addiction',
            'بیماری های خاص' => 'special_disease',
            'از کار افتادگی' => 'work_disability',
            'بیکاری' => 'unemployment',
        ];

        return $mapping[$persian] ?? $persian;
    }

    /**
     * دریافت تمام مقادیر فارسی
     */
    public static function getPersianValues(): array
    {
        return [
            'اعتیاد',
            'بیماری های خاص',
            'از کار افتادگی',
            'بیکاری',
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
     * بررسی اینکه آیا مقدار معتبر است
     */
    public static function isValidValue(string $value): bool
    {
        $allValues = array_merge(
            self::getPersianValues(),
            self::getEnglishValues()
        );
        
        return in_array($value, $allValues);
    }
} 