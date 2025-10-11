<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\CustomExcelValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CustomExcelValueBinderTest extends TestCase
{
    private CustomExcelValueBinder $binder;
    private Spreadsheet $spreadsheet;
    private Worksheet $worksheet;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->binder = new CustomExcelValueBinder();
        $this->spreadsheet = new Spreadsheet();
        $this->worksheet = $this->spreadsheet->getActiveSheet();
    }

    /** @test */
    public function it_converts_long_numbers_in_column_a_to_string()
    {
        // عدد 17 رقمی (کد خانوار)
        $longNumber = '20250929012138721';
        
        // دریافت سلول A1
        $cell = $this->worksheet->getCell('A1');
        
        // استفاده از binder برای تنظیم مقدار
        $this->binder->bindValue($cell, $longNumber);
        
        // بررسی که نوع داده رشته است
        $this->assertEquals(DataType::TYPE_STRING, $cell->getDataType());
        
        // بررسی که مقدار حفظ شده
        $this->assertEquals($longNumber, $cell->getValue());
    }

    /** @test */
    public function it_converts_scientific_notation_in_column_a_to_string()
    {
        // عدد با نماد علمی
        $scientificNumber = '2.0250929012138721E+16';
        
        $cell = $this->worksheet->getCell('A2');
        $this->binder->bindValue($cell, $scientificNumber);
        
        // باید به صورت رشته ذخیره شود
        $this->assertEquals(DataType::TYPE_STRING, $cell->getDataType());
    }

    /** @test */
    public function it_preserves_normal_numbers_in_other_columns()
    {
        // عدد معمولی در ستون B (مثلاً مبلغ بیمه)
        $normalNumber = 1500000;
        
        $cell = $this->worksheet->getCell('B1');
        $this->binder->bindValue($cell, $normalNumber);
        
        // نوع داده باید عددی باشد (نه رشته)
        $this->assertEquals(DataType::TYPE_NUMERIC, $cell->getDataType());
        $this->assertEquals($normalNumber, $cell->getValue());
    }

    /** @test */
    public function it_preserves_short_numbers_in_column_a()
    {
        // عدد کوتاه (کمتر از 15 رقم) در ستون A
        $shortNumber = 123456789;
        
        $cell = $this->worksheet->getCell('A3');
        $this->binder->bindValue($cell, $shortNumber);
        
        // برای اعداد کوتاه، نوع داده عددی حفظ می‌شود
        $this->assertEquals(DataType::TYPE_NUMERIC, $cell->getDataType());
        $this->assertEquals($shortNumber, $cell->getValue());
    }

    /** @test */
    public function it_preserves_text_values_in_all_columns()
    {
        // مقدار متنی
        $textValue = 'کد خانوار';
        
        $cell = $this->worksheet->getCell('A4');
        $this->binder->bindValue($cell, $textValue);
        
        // متن باید به صورت رشته ذخیره شود
        $this->assertEquals(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertEquals($textValue, $cell->getValue());
    }

    /** @test */
    public function it_handles_16_digit_numbers_correctly()
    {
        // عدد 16 رقمی (بیشتر از 15)
        $number16Digit = '1234567890123456';
        
        $cell = $this->worksheet->getCell('A5');
        $this->binder->bindValue($cell, $number16Digit);
        
        // باید به صورت رشته ذخیره شود
        $this->assertEquals(DataType::TYPE_STRING, $cell->getDataType());
        $this->assertEquals($number16Digit, $cell->getValue());
    }
}
