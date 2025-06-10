<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting; // اضافه شده
use PhpOffice\PhpSpreadsheet\Style\NumberFormat; // اضافه شده

class DynamicDataExport implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting // اینترفیس اضافه شده
{
    protected $collection;
    protected $headings;
    protected $dataKeys;

    /**
     * @param Collection $collection  مجموعه داده‌ها برای خروجی
     * @param array      $headings    آرایه‌ای از نام ستون‌ها برای نمایش در هدر فایل اکسل
     * @param array      $dataKeys    آرایه‌ای از کلیدهای متناظر با هر هدر برای استخراج داده از collection
     */
    public function __construct(Collection $collection, array $headings, array $dataKeys)
    {
        $this->collection = $collection;
        $this->headings = $headings;
        $this->dataKeys = $dataKeys;
    }

    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        $mappedRow = [];
        foreach ($this->dataKeys as $key) {
            // با استفاده از data_get می‌توانیم به سادگی به داده‌های تو در تو (nested) هم دسترسی پیدا کنیم
            $value = data_get($row, $key, '---');
            
            // اصلاح برای شمارش صحیح تعداد اعضا
            if (str_contains($key, 'members_count') || str_contains($key, 'members.count')) {
                $value = $row->members->count();
            }
            
            // اضافه کردن پشتیبانی از Enum
            if ($value instanceof \App\InsuranceWizardStep) {
                $value = $value->label();
            }
            
            $mappedRow[] = $value;
        }
        return $mappedRow;
    }
    
    /**
     * فرمت ستون‌ها را مشخص می‌کند.
     */
    public function columnFormats(): array
    {
        $formats = [];
        
        // پیدا کردن ستون کد ملی
        foreach ($this->dataKeys as $index => $key) {
            // اگر کلید شامل national_code باشد، آن را به عنوان متن فرمت می‌کنیم
            if (str_contains($key, 'national_code')) {
                // تبدیل شماره ستون به حرف (0=A, 1=B, 2=C, ...)
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index);
                $formats[$column] = NumberFormat::FORMAT_TEXT;
            }
        }
        
        return $formats;
    }
}