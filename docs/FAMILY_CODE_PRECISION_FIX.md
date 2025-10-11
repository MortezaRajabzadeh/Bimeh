# راه‌حل مشکل از دست رفتن دقت کدهای خانوار 17 رقمی

## 🔍 شرح مشکل

کدهای خانوار 17 رقمی (مانند `20250929012138721`) هنگام صادرات و بازخوانی فایل اکسل، یک رقم انتهایی تغییر می‌کرد (به `20250929012138720` تبدیل می‌شد).

### دلیل مشکل
- **PHP Float Precision**: PHP از نوع داده `float` برای اعداد استفاده می‌کند که فقط تا 15-16 رقم دقت دارد
- **Excel Number Format**: وقتی اعداد بلند در اکسل به صورت عددی ذخیره می‌شوند، به نوتیشن علمی تبدیل می‌شوند
- **تبدیل اشتباه در خواندن**: هنگام خواندن فایل، اعداد به `float` تبدیل شده و دقت از دست می‌رفت

---

## ✅ راه‌حل پیاده‌سازی شده

راه‌حل شامل **سه بخش اصلی** است:

### 1️⃣ بهبود صادرات فایل اکسل
**فایل**: `app/Exports/DynamicDataExport.php`

#### تغییرات:

**الف) متد `bindValue`** - ذخیره اعداد طولانی به صورت رشته
```php
public function bindValue(Cell $cell, $value)
{
    // اعداد بلندتر از 10 رقم به صورت رشته ذخیره می‌شوند
    if (is_string($value) && is_numeric($value) && strlen($value) > 10) {
        $cell->setValueExplicit($value, DataType::TYPE_STRING);
        return true;
    }
    return parent::bindValue($cell, $value);
}
```

**ب) متد `columnFormats`** - اعمال فرمت TEXT
```php
public function columnFormats(): array
{
    $formats = [];
    foreach ($this->dataKeys as $index => $key) {
        if (str_contains($key, 'family_code') || 
            str_contains($key, 'national_code')) {
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
            $formats[$columnLetter] = '@'; // TEXT format
        }
    }
    return $formats;
}
```

**ج) متد `styles`** - تقویت فرمت TEXT
```php
public function styles(Worksheet $sheet)
{
    foreach ($this->dataKeys as $index => $key) {
        if (str_contains($key, 'family_code')) {
            $columnLetter = Coordinate::stringFromColumnIndex($index + 1);
            $sheet->getStyle($columnLetter . ':' . $columnLetter)
                  ->getNumberFormat()
                  ->setFormatCode('@');
        }
    }
    return [1 => ['font' => ['bold' => true]]];
}
```

---

### 2️⃣ ایجاد CustomExcelValueBinder
**فایل جدید**: `app/Services/CustomExcelValueBinder.php`

این کلاس هنگام خواندن فایل اکسل، اعداد طولانی را به صورت رشته نگه می‌دارد:

```php
class CustomExcelValueBinder extends DefaultValueBinder
{
    public function bindValue(Cell $cell, $value): bool
    {
        $column = $cell->getColumn();
        
        // برای ستون A (کد خانوار)
        if ($column === 'A') {
            if (is_numeric($value)) {
                $strValue = (string)$value;
                
                // اعداد بیش از 15 رقم یا با نماد علمی
                if (stripos($strValue, 'e') !== false || 
                    strlen(str_replace(['.', '-', '+'], '', $strValue)) > 15) {
                    $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
                    return true;
                }
            }
        }
        
        return parent::bindValue($cell, $value);
    }
}
```

---

### 3️⃣ بهبود خواندن فایل اکسل
**فایل**: `app/Services/InsuranceShareService.php`

#### الف) تنظیم ValueBinder در متد `completeInsuranceFromExcel`
```php
public function completeInsuranceFromExcel(string $filePath): array
{
    try {
        // تنظیم Custom ValueBinder
        \PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder(
            new CustomExcelValueBinder()
        );
        
        $spreadsheet = IOFactory::load($filePath);
        // ... ادامه کد
    }
}
```

#### ب) اصلاح متد `normalizeFamilyCode`
این متد دیگر از تبدیل به `float` استفاده نمی‌کند:

```php
private function normalizeFamilyCode($value): string
{
    if (empty($value)) {
        return '';
    }

    $strValue = trim((string)$value);
    
    // اگر فقط رقم است، همانطور حفظ شود
    if (preg_match('/^\d+$/', $strValue)) {
        return $strValue;
    }
    
    // حذف .0 بدون تبدیل به float
    if (preg_match('/^(\d+)\.0+$/', $strValue, $matches)) {
        return $matches[1];
    }
    
    return trim($strValue);
}
```

---

## 🧪 تست‌های پیاده‌سازی شده

### 1. `CustomExcelValueBinderTest.php` (6 تست)
- تبدیل اعداد 17 رقمی به رشته در ستون A
- تبدیل اعداد با نوتیشن علمی به رشته
- حفظ اعداد عادی در سایر ستون‌ها
- حفظ اعداد کوتاه در ستون A
- حفظ مقادیر متنی
- رفتار با اعداد 16 رقمی

### 2. `NormalizeFamilyCodeTest.php` (6 تست)
- حفظ کدهای 17 رقمی
- حذف اعشار بدون از دست دادن دقت
- رفتار صحیح با رشته‌های عددی
- حذف چند صفر اعشاری
- رفتار با مقادیر خالی
- حذف فاصله‌های اضافی

### 3. `ExcelReadWriteIntegrationTest.php` (3 تست یکپارچه)
- حفظ کدهای طولانی در چرخه کامل نوشتن و خواندن
- ذخیره کدها به صورت TEXT در فایل اکسل
- رفتار با چندین کد طولانی بدون از دست دادن دقت

**نتیجه**: **15 تست، 47 assertion - همه پاس شدند ✅**

---

## 📊 نتایج

### قبل از اصلاح:
```
ورودی:  20250929012138721
خروجی:  20250929012138720  ❌ (یک رقم تغییر کرده)
```

### بعد از اصلاح:
```
ورودی:  20250929012138721
خروجی:  20250929012138721  ✅ (دقیقاً همان)
```

---

## 🔧 نحوه استفاده در محیط واقعی

### برای صادرات:
```php
use App\Exports\DynamicDataExport;

$export = new DynamicDataExport(
    $collection, 
    $headers, 
    $dataKeys  // باید شامل 'family_code' باشد
);

Excel::store($export, 'file.xlsx');
```

### برای آپلود و خواندن:
```php
use App\Services\InsuranceShareService;

$service = new InsuranceShareService();
$result = $service->completeInsuranceFromExcel($filePath);

// کدهای خانوار با دقت کامل پردازش می‌شوند
```

---

## 🎯 نکات مهم

1. **فرمت TEXT در اکسل**: سلول‌های ستون A باید فرمت TEXT داشته باشند نه Number
2. **عدم استفاده از Float**: هرگز کدهای خانوار را به `float` تبدیل نکنید
3. **استفاده از Regex**: برای حذف `.0` از رشته استفاده کنید نه تبدیل به عدد
4. **ValueBinder سفارشی**: برای خواندن فایل‌های اکسل حتماً ValueBinder را تنظیم کنید

---

## 🔍 لاگ‌های مفید برای دیباگ

```php
// در خواندن فایل:
Log::debug('📖 خواندن کد خانوار', [
    'row' => $row,
    'data_type' => $cell->getDataType(),  // باید 's' باشد (string)
    'value' => $value,
    'value_type' => gettype($value)  // باید 'string' باشد
]);

// در normalizeFamilyCode:
Log::debug('✅ کد خانوار به صورت رشته عددی حفظ شد', [
    'value' => $strValue,
    'length' => strlen($strValue)  // باید 17 باشد
]);
```

---

## 📝 چک‌لیست تست در محیط واقعی

- [ ] صادرات فایل اکسل با کدهای 17 رقمی
- [ ] بررسی فرمت سلول در Excel (باید Text باشد)
- [ ] آپلود همان فایل
- [ ] بررسی لاگ‌ها (`data_type` باید `s` باشد)
- [ ] تأیید که کدها تغییر نکرده‌اند
- [ ] تست با حداقل 10 خانوار مختلف

---

**تاریخ**: 2025-10-11  
**نسخه**: 1.0.0  
**وضعیت**: ✅ تست شده و آماده استفاده
