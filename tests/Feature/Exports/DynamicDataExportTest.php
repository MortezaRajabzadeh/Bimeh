<?php

namespace Tests\Feature\Exports;

use Tests\TestCase;
use App\Exports\DynamicDataExport;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DynamicDataExportTest extends TestCase
{
    /**
     * تست فرمت متنی ستون‌های حاوی کدهای طولانی
     * 
     * @test
     */
    public function it_formats_family_code_column_as_text()
    {
        // داده‌های تست با کد خانوار طولانی
        $testData = collect([
            [
                'family_code' => '20250929012138721', // 17 رقم - باید به صورت TEXT ذخیره شود
                'national_code' => '1234567890',
                'name' => 'تست خانواده',
            ]
        ]);

        $headings = ['کد خانوار', 'کد ملی', 'نام'];
        $dataKeys = ['family_code', 'national_code', 'name'];

        $export = new DynamicDataExport($testData, $headings, $dataKeys);

        // بررسی columnFormats
        $formats = $export->columnFormats();
        
        $this->assertArrayHasKey('A', $formats, 'ستون A (family_code) باید فرمت داشته باشد');
        $this->assertEquals('@', $formats['A'], 'فرمت ستون A باید @ (TEXT) باشد');
        
        $this->assertArrayHasKey('B', $formats, 'ستون B (national_code) باید فرمت داشته باشد');
        $this->assertEquals('@', $formats['B'], 'فرمت ستون B باید @ (TEXT) باشد');
        
        $this->assertArrayNotHasKey('C', $formats, 'ستون C (name) نباید فرمت خاصی داشته باشد');
    }

    /**
     * تست اینکه متد styles به درستی کار می‌کند
     * 
     * @test
     */
    public function it_applies_text_format_via_styles_method()
    {
        $testData = collect([
            [
                'family_code' => '20250929012138721',
                'name' => 'تست',
            ]
        ]);

        $headings = ['کد خانوار', 'نام'];
        $dataKeys = ['family_code', 'name'];

        $export = new DynamicDataExport($testData, $headings, $dataKeys);

        // ایجاد یک فایل موقت
        $fileName = 'test_export_' . time() . '.xlsx';
        $tempFile = storage_path('app/' . $fileName);
        
        try {
            // ذخیره فایل
            Excel::store($export, $fileName, 'local');
            
            // خواندن فایل با PhpSpreadsheet
            $spreadsheet = IOFactory::load($tempFile);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // بررسی فرمت ستون A
            $columnAFormat = $worksheet->getStyle('A2')->getNumberFormat()->getFormatCode();
            
            $this->assertEquals('@', $columnAFormat, 'فرمت ستون A در فایل Excel باید @ (TEXT) باشد');
            
            // بررسی مقدار سلول - باید دقیقاً همان عدد باشد
            $cellValue = $worksheet->getCell('A2')->getValue();
            $this->assertEquals('20250929012138721', $cellValue, 'مقدار کد خانوار باید دقیقاً حفظ شود');
            
            // بررسی که عدد به نوتیشن علمی تبدیل نشده
            $this->assertStringNotContainsString('E+', (string)$cellValue, 'مقدار نباید به نوتیشن علمی تبدیل شود');
            
        } finally {
            // پاک کردن فایل موقت
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * تست اینکه چندین ستون کدی به درستی فرمت می‌شوند
     * 
     * @test
     */
    public function it_formats_multiple_code_columns_as_text()
    {
        $testData = collect([
            [
                'family_code' => '20250929012138721',
                'national_code' => '0123456789',
                'household_code' => '98765432109876543',
                'other_field' => 'عادی',
            ]
        ]);

        $headings = ['کد خانوار', 'کد ملی', 'کد خانوارداری', 'دیگر'];
        $dataKeys = ['family_code', 'national_code', 'household_code', 'other_field'];

        $export = new DynamicDataExport($testData, $headings, $dataKeys);

        $formats = $export->columnFormats();
        
        // بررسی که همه ستون‌های کدی فرمت TEXT دارند
        $this->assertEquals('@', $formats['A'], 'ستون A (family_code) باید TEXT باشد');
        $this->assertEquals('@', $formats['B'], 'ستون B (national_code) باید TEXT باشد');
        $this->assertEquals('@', $formats['C'], 'ستون C (household_code) باید TEXT باشد');
        $this->assertArrayNotHasKey('D', $formats, 'ستون D (other_field) نباید فرمت TEXT داشته باشد');
    }

    /**
     * تست اینکه ردیف هدر بولد می‌شود
     * 
     * @test
     */
    public function it_makes_header_row_bold()
    {
        $testData = collect([
            ['family_code' => '12345', 'name' => 'تست']
        ]);

        $headings = ['کد', 'نام'];
        $dataKeys = ['family_code', 'name'];

        $export = new DynamicDataExport($testData, $headings, $dataKeys);

        // ایجاد worksheet موقت برای تست
        $fileName = 'test_header_' . time() . '.xlsx';
        $tempFile = storage_path('app/' . $fileName);
        
        try {
            Excel::store($export, $fileName, 'local');
            
            $spreadsheet = IOFactory::load($tempFile);
            $worksheet = $spreadsheet->getActiveSheet();
            
            // بررسی که ردیف اول بولد است
            $isBold = $worksheet->getStyle('A1')->getFont()->getBold();
            
            $this->assertTrue($isBold, 'هدر (ردیف اول) باید بولد باشد');
            
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * تست اینکه عدد خیلی بزرگ دقت خود را حفظ می‌کند
     * 
     * @test
     */
    public function it_preserves_precision_of_very_long_numbers()
    {
        $longCode = '20250929012138721'; // 17 رقم
        
        $testData = collect([
            ['family_code' => $longCode, 'name' => 'تست']
        ]);

        $headings = ['کد خانوار', 'نام'];
        $dataKeys = ['family_code', 'name'];

        $export = new DynamicDataExport($testData, $headings, $dataKeys);

        $fileName = 'test_precision_' . time() . '.xlsx';
        $tempFile = storage_path('app/' . $fileName);
        
        try {
            Excel::store($export, $fileName, 'local');
            
            $spreadsheet = IOFactory::load($tempFile);
            $worksheet = $spreadsheet->getActiveSheet();
            
            $savedValue = $worksheet->getCell('A2')->getValue();
            
            // بررسی که تمام 17 رقم حفظ شده
            $this->assertEquals($longCode, $savedValue, 'تمام ارقام کد خانوار باید حفظ شود');
            $this->assertEquals(strlen($longCode), strlen($savedValue), 'طول رشته باید یکسان باشد');
            
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * تست اینکه Collection خالی مشکلی ایجاد نمی‌کند
     * 
     * @test
     */
    public function it_handles_empty_collection()
    {
        $testData = collect([]);
        $headings = ['کد خانوار', 'نام'];
        $dataKeys = ['family_code', 'name'];

        $export = new DynamicDataExport($testData, $headings, $dataKeys);

        $formats = $export->columnFormats();
        
        // باید بدون خطا اجرا شود و فرمت‌ها را برگرداند
        $this->assertIsArray($formats);
        $this->assertEquals('@', $formats['A'] ?? null);
    }
}
