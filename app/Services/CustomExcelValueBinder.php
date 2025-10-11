<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Custom Value Binder برای حفظ دقت اعداد طولانی در ستون A (کد خانوار)
 * 
 * این کلاس از DefaultValueBinder ارث‌بری می‌کند و رفتار پیش‌فرض را برای ستون A تغییر می‌دهد
 * تا اعداد خیلی بزرگ (بیش از 15 رقم) را به صورت رشته ذخیره کند و از دست رفتن دقت جلوگیری کند.
 */
class CustomExcelValueBinder extends DefaultValueBinder
{
    /**
     * Bind value to a cell
     *
     * @param Cell $cell سلولی که مقدار به آن اختصاص داده می‌شود
     * @param mixed $value مقدار ورودی
     * @return bool
     */
    public function bindValue(Cell $cell, $value): bool
    {
        // دریافت ستون سلول (A, B, C, ...)
        $column = $cell->getColumn();
        
        // برای ستون A (کد خانوار)
        if ($column === 'A') {
            // اگر مقدار عددی است
            if (is_numeric($value)) {
                // تبدیل به رشته و چک طول
                $strValue = (string)$value;
                
                // اگر شامل نماد علمی است یا طول آن بیش از 15 رقم است
                if (stripos($strValue, 'e') !== false || strlen(str_replace(['.', '-', '+'], '', $strValue)) > 15) {
                    // ذخیره به صورت رشته
                    $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
                    return true;
                }
            }
        }
        
        // برای سایر ستون‌ها یا مقادیر، از رفتار پیش‌فرض استفاده کن
        return parent::bindValue($cell, $value);
    }
}
