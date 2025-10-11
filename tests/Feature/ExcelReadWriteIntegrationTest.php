<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Exports\DynamicDataExport;
use App\Services\CustomExcelValueBinder;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Cell;

class ExcelReadWriteIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    /** @test */
    public function it_preserves_long_family_codes_through_complete_export_import_cycle()
    {
        // داده‌های آزمایشی با کدهای خانوار طولانی
        $headers = ['کد خانوار', 'نام', 'مبلغ'];
        $dataKeys = ['family_code', 'name', 'amount'];
        
        $testData = collect([
            (object)['family_code' => '20250929012138721', 'name' => 'خانواده اول', 'amount' => 1500000],
            (object)['family_code' => '20250929012138722', 'name' => 'خانواده دوم', 'amount' => 2000000],
            (object)['family_code' => '12345678901234567', 'name' => 'خانواده سوم', 'amount' => 1800000],
        ]);
        
        $export = new DynamicDataExport($testData, $headers, $dataKeys);
        
        // ذخیره فایل
        $filePath = 'test_exports/family_codes_test.xlsx';
        Excel::store($export, $filePath, 'local');
        
        // بررسی که فایل ایجاد شده
        $this->assertTrue(Storage::disk('local')->exists($filePath));
        
        // تنظیم CustomValueBinder برای خواندن
        Cell::setValueBinder(new CustomExcelValueBinder());
        
        // خواندن فایل با PhpSpreadsheet
        $fullPath = Storage::disk('local')->path($filePath);
        $spreadsheet = IOFactory::load($fullPath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        // بررسی ردیف اول داده (ردیف 2 در اکسل - ردیف 1 هدر است)
        $row2_familyCode = $worksheet->getCell('A2')->getValue();
        $row2_name = $worksheet->getCell('B2')->getValue();
        $row2_amount = $worksheet->getCell('C2')->getValue();
        
        // تأیید که کد خانوار حفظ شده (17 رقم کامل)
        $this->assertEquals('20250929012138721', $row2_familyCode, 'کد خانوار ردیف اول باید حفظ شود');
        $this->assertEquals('خانواده اول', $row2_name);
        $this->assertEquals(1500000, $row2_amount);
        
        // بررسی ردیف دوم
        $row3_familyCode = $worksheet->getCell('A3')->getValue();
        $this->assertEquals('20250929012138722', $row3_familyCode, 'کد خانوار ردیف دوم باید حفظ شود');
        
        // بررسی ردیف سوم
        $row4_familyCode = $worksheet->getCell('A4')->getValue();
        $this->assertEquals('12345678901234567', $row4_familyCode, 'کد خانوار ردیف سوم باید حفظ شود');
        
        // لاگ برای دیباگ
        dump([
            'row_2_family_code' => $row2_familyCode,
            'row_2_family_code_type' => gettype($row2_familyCode),
            'row_2_family_code_length' => strlen((string)$row2_familyCode),
            'row_3_family_code' => $row3_familyCode,
            'row_4_family_code' => $row4_familyCode,
        ]);
    }

    /** @test */
    public function it_stores_family_codes_as_text_in_excel_file()
    {
        $headers = ['کد خانوار', 'نام'];
        $dataKeys = ['family_code', 'name'];
        
        $testData = collect([
            (object)['family_code' => '20250929012138721', 'name' => 'تست'],
        ]);
        
        $export = new DynamicDataExport($testData, $headers, $dataKeys);
        
        $filePath = 'test_exports/text_format_test.xlsx';
        Excel::store($export, $filePath, 'local');
        
        // خواندن فایل
        Cell::setValueBinder(new CustomExcelValueBinder());
        $fullPath = Storage::disk('local')->path($filePath);
        $spreadsheet = IOFactory::load($fullPath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        $cell = $worksheet->getCell('A2');
        
        // بررسی نوع داده سلول
        $this->assertEquals(
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING,
            $cell->getDataType(),
            'کد خانوار باید به صورت رشته (text) ذخیره شود'
        );
        
        // بررسی مقدار
        $this->assertEquals('20250929012138721', $cell->getValue());
    }

    /** @test */
    public function it_handles_multiple_long_codes_without_losing_precision()
    {
        // تست با 10 کد خانوار مختلف
        $headers = ['کد خانوار'];
        $dataKeys = ['family_code'];
        
        $expectedCodes = [];
        $testDataArray = [];
        for ($i = 1; $i <= 10; $i++) {
            $code = '2025092901213872' . $i; // 17 رقم
            $expectedCodes[] = $code;
            $testDataArray[] = (object)['family_code' => $code];
        }
        
        $testData = collect($testDataArray);
        $export = new DynamicDataExport($testData, $headers, $dataKeys);
        
        $filePath = 'test_exports/multiple_codes_test.xlsx';
        Excel::store($export, $filePath, 'local');
        
        // خواندن و بررسی
        Cell::setValueBinder(new CustomExcelValueBinder());
        $fullPath = Storage::disk('local')->path($filePath);
        $spreadsheet = IOFactory::load($fullPath);
        $worksheet = $spreadsheet->getActiveSheet();
        
        for ($i = 0; $i < 10; $i++) {
            $row = $i + 2; // ردیف 2 تا 11 (ردیف 1 هدر است)
            $actualCode = $worksheet->getCell('A' . $row)->getValue();
            
            $this->assertEquals(
                $expectedCodes[$i],
                $actualCode,
                "کد خانوار در ردیف {$row} باید {$expectedCodes[$i]} باشد ولی {$actualCode} است"
            );
        }
    }
}
