<?php

namespace App\Exports;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
/**
 * ???? Export ???????? ???? ????? ????
 * 
 * ??? ???? ?? ???? ???????? ??????? ?? ?? ???? ???? ????? ??????
 */
class DynamicDataExport extends DefaultValueBinder implements FromCollection, WithHeadings, WithMapping, WithColumnFormatting, WithColumnWidths, WithStyles, WithCustomValueBinder
{
    /**
     * ?????? ??????? ???? ?????
     *
     * @var Collection
     */
    protected $collection;
    /**
     * ?????? ???????? ????
     *
     * @var array
     */
    protected $headings;
    /**
     * ??????? ???? ???? ?????
     *
     * @var array
     */
    protected $dataKeys;
    /**
     * ?????? ????
     *
     * @param Collection $collection ?????? ???????
     * @param array $headings ?????? ???????
     * @param array $dataKeys ??????? ???? ???? ???????
     */
    public function __construct(Collection $collection, array $headings, array $dataKeys)
    {
        $this->collection = $collection;
        $this->headings = $headings;
        $this->dataKeys = $dataKeys;
    }
    /**
     * ?????????? ?????? ???????
     *
     * @return Collection
     */
    public function collection()
    {
        return $this->collection;
    }
    /**
     * ?????????? ?????? ???????
     *
     * @return array
     */
    public function headings(): array
    {
        return $this->headings;
    }
    /**
     * ????? ?? ???? ???? ?? ?????
     *
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        $mappedRow = [];
        foreach ($this->dataKeys as $key) {
            // ??????? ????? ?? ???????? ?? ??????? ?? ?? ?? (??? 'head.name')
            $value = data_get($row, $key, '---');
            // ?????? ???? ???: ????? ????? ???????
            if (str_contains($key, 'members_count') || str_contains($key, 'members.count')) {
                $value = isset($row->members) ? $row->members->count() : 0;
            }
            // ????? Enum ?? ?????
            if ($value instanceof \App\Enums\InsuranceWizardStep) {
                $value = $value->label();
            }
            
            // تبدیل اجباری به رشته برای ستون‌های کدی
            if ((str_contains($key, 'national_code') || str_contains($key, 'family_code') || str_contains($key, 'household_code')) && !empty($value)) {
                // تبدیل به رشته و حفظ تمام ارقام
                $value = (string) $value;
            }
            
            $mappedRow[] = $value;
        }
        return $mappedRow;
    }
    /**
     * ????? ???? ???????? ????
     *
     * @return array
     */
    public function columnFormats(): array
    {
        $formats = [];
        // ???? ???? ???????? ?? ??? ? ????? ???? ???? ???? ?????
        foreach ($this->dataKeys as $index => $key) {
            if (str_contains($key, 'national_code') || str_contains($key, 'family_code') || str_contains($key, 'household_code')) {
                // ????? ????? ?? ??? ???? ???? (0 -> A, 1 -> B, ...)
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                // ????? ???? ?? ??? ?? ???? ?? ??? ?? ?? ????? ??? ?????
                $formats[$columnLetter] = '@'; // فرمت @ مستقیم برای TEXT
            }
        }
        return $formats;
    }
    /**
     * ????? ??? ???????? ????
     *
     * @return array
     */
    public function columnWidths(): array
    {
        $widths = [];
        // ????? ??? ????? ???? ???? ???????
        foreach ($this->dataKeys as $index => $key) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            // ??? ??????? 20 ???? (????? ?????? 150 ?????)
            // ??? ????? ?? ?????? ???? PhpSpreadsheet ???? ????
            $widths[$columnLetter] = 20;
        }
        return $widths;
    }

    /**
     * اعمال فرمت TEXT به ستون‌های حاوی اعداد طولانی
     * این متد از تبدیل به نوتیشن علمی جلوگیری می‌کند
     */
    public function styles(Worksheet $sheet)
    {
        // اعمال فرمت TEXT به ستون‌های کدی
        foreach ($this->dataKeys as $index => $key) {
            if (str_contains($key, 'national_code') || str_contains($key, 'family_code') || str_contains($key, 'household_code')) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                // اعمال مستقیم فرمت TEXT بر کل ستون
                $sheet->getStyle($columnLetter . ':' . $columnLetter)
                      ->getNumberFormat()
                      ->setFormatCode('@');
            }
        }

        return [
            // فرمت ردیف هدر (بولد)
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * کنترل دستی نحوه ذخیره مقادیر در سلول‌ها
     * این متد اجازه می‌دهد به صورت صریح نوع داده را تعیین کنیم
     */
    public function bindValue(Cell $cell, $value)
    {
        // بررسی اینکه آیا مقدار یک کد طولانی عددی است
        if (is_string($value) && is_numeric($value) && strlen($value) > 10) {
            // ذخیره به صورت صریح به عنوان STRING
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        // برای بقیه موارد از روش پیش‌فرض استفاده کن
        return parent::bindValue($cell, $value);
    }
}
