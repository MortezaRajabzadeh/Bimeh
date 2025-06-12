<?php

namespace App\Helpers;

use Morilog\Jalali\Jalalian;
use Carbon\Carbon;

class DateHelper
{
    /**
     * تبدیل تاریخ میلادی به شمسی
     *
     * @param string|Carbon $date
     * @param string $format
     * @return string
     */
    public static function toJalali($date, string $format = 'Y/m/d H:i'): string
    {
        if (empty($date)) {
            return '';
        }

        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        return Jalalian::fromCarbon($date)->format($format);
    }

    /**
     * تبدیل تاریخ شمسی به میلادی
     *
     * @param string $jalaliDate
     * @return Carbon
     */
    public static function toGregorian(string $jalaliDate): Carbon
    {
        if (empty($jalaliDate)) {
            return Carbon::now();
        }

        return Jalalian::fromFormat('Y/m/d', $jalaliDate)->toCarbon();
    }

    /**
     * نمایش تاریخ به صورت نسبی (مثلا: ۳ روز پیش)
     *
     * @param string|Carbon $date
     * @return string
     */
    public static function diffForHumans($date): string
    {
        if (empty($date)) {
            return '';
        }

        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        return Jalalian::fromCarbon($date)->ago();
    }
} 
