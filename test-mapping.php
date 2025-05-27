<?php

// Test mapping logic
$fieldMapping = [
    // اطلاعات آدرس - فارسی
    'استان' => 'province_name',
    'شهر' => 'city_name', 
    'دهستان' => 'district',
    'آدرس' => 'address',
    'کد پستی' => 'postal_code',
    'وضعیت مسکن' => 'housing_status',
    'توضیحات مسکن' => 'housing_description',
    
    // اطلاعات آدرس - انگلیسی (از فایل کاربر)
    'astan' => 'province_name',
    'shhr' => 'city_name',
    'dhstan' => 'district', 
    'adrs' => 'address',
    'kd_psty' => 'postal_code',
    'odaayt_mskn' => 'housing_status',
    'todyhat_mskn' => 'housing_description',
    
    // اطلاعات عضو - فارسی  
    'نام' => 'first_name',
    'نام خانوادگی' => 'last_name', 
    'کد ملی' => 'national_code',
    'نام پدر' => 'father_name',
    'تاریخ تولد' => 'birth_date',
    'جنسیت' => 'gender',
    'نسبت خانوادگی' => 'relationship',
    'وضعیت تأهل' => 'marital_status',
    'تحصیلات' => 'education',
    'معلولیت' => 'has_disability',
    'بیماری مزمن' => 'has_chronic_disease',
    'داشتن بیمه' => 'has_insurance',
    'نوع بیمه' => 'insurance_type',
    'شرایط خاص' => 'special_conditions',
    'شغل' => 'occupation',
    'وضعیت اشتغال' => 'is_employed',
    'موبایل' => 'mobile',
    'تلفن' => 'phone',
    
    // اطلاعات عضو - انگلیسی (از فایل کاربر)
    'nam' => 'first_name',
    'nam_khanoadgy' => 'last_name',
    'kd_mly' => 'national_code', 
    'nam_pdr' => 'father_name',
    'tarykh_told' => 'birth_date',
    'gnsyt' => 'gender',
    'nsbt_khanoadgy' => 'relationship',
    'odaayt_tahl' => 'marital_status',
    'thsylat' => 'education',
    'maalolyt' => 'has_disability',
    'bymary_mzmn' => 'has_chronic_disease',
    'dashtn_bymh' => 'has_insurance',
    'noaa_bymh' => 'insurance_type',
    'shrayt_khas' => 'special_conditions',
    'shghl' => 'occupation',
    'odaayt_ashtghal' => 'is_employed',
    'mobayl' => 'mobile',
    'tlfn' => 'phone',
];

function mapRow(array $row, array $fieldMapping): array
{
    $mapped = [];
    
    foreach ($row as $key => $value) {
        // حذف فضاهای اضافی و کاراکترهای غیرقابل رؤیت
        $normalizedKey = trim(preg_replace('/\s+/', ' ', $key));
        $normalizedValue = trim($value ?? '');
        
        if (isset($fieldMapping[$normalizedKey])) {
            $mapped[$fieldMapping[$normalizedKey]] = $normalizedValue;
        } else {
            // Log unmapped fields for debugging
            echo "Unmapped field found: '{$normalizedKey}' (original: '{$key}')\n";
        }
    }
    
    return $mapped;
}

// Test with sample data from log
$testRow = [
    'astan' => 'تهران',
    'shhr' => 'تهران', 
    'dhstan' => 'مرکزی',
    'adrs' => 'خیابان آزادی، کوچه نور، پلاک 15',
    'kd_psty' => '1234567890',
    'odaayt_mskn' => 'مستأجر',
    'todyhat_mskn' => 'آپارتمان 2 خوابه طبقه سوم',
    'nam' => 'فاطمه',
    'nam_khanoadgy' => 'احمدی',
    'kd_mly' => '9876543210'
];

echo "Testing mapping with sample data:\n";
echo "Original data:\n";
print_r($testRow);

echo "\nMapped data:\n";
$mapped = mapRow($testRow, $fieldMapping);
print_r($mapped);

echo "\nChecking required fields:\n";
$firstName = trim($mapped['first_name'] ?? '');
$nationalCode = trim($mapped['national_code'] ?? '');

echo "First name: '{$firstName}' (length: " . strlen($firstName) . ")\n";
echo "National code: '{$nationalCode}' (length: " . strlen($nationalCode) . ")\n";

if (empty($firstName) || empty($nationalCode)) {
    echo "ERROR: Required fields are empty!\n";
} else {
    echo "SUCCESS: Required fields are filled!\n";
} 