<?php

// Test headers from user file
$headers = [
    'astan', 'shhr', 'dhstan', 'adrs', 'kd_psty', 'odaayt_mskn', 'todyhat_mskn',
    'nam', 'nam_khanoadgy', 'kd_mly', 'nam_pdr', 'tarykh_told', 'gnsyt', 
    'nsbt_khanoadgy', 'odaayt_tahl', 'thsylat', 'maalolyt', 'bymary_mzmn',
    'dashtn_bymh', 'noaa_bymh', 'shrayt_khas', 'shghl', 'odaayt_ashtghal',
    'mobayl', 'tlfn'
];

echo "Headers from user file:\n";
foreach ($headers as $header) {
    echo "'{$header}'\n";
}

echo "\nTest data:\n";
$testData = [
    [
        'astan' => 'تهران',
        'shhr' => 'تهران', 
        'dhstan' => 'مرکزی',
        'adrs' => 'خیابان آزادی، کوچه نور، پلاک 15',
        'kd_psty' => '1234567890',
        'odaayt_mskn' => 'مستأجر',
        'todyhat_mskn' => 'آپارتمان 2 خوابه طبقه سوم',
        'nam' => 'احمد',
        'nam_khanoadgy' => 'احمدی',
        'kd_mly' => '1234567890',
        'nam_pdr' => 'علی',
        'tarykh_told' => '1360/01/01',
        'gnsyt' => 'مرد',
        'nsbt_khanoadgy' => 'سرپرست',
        'odaayt_tahl' => 'متأهل',
        'thsylat' => 'دیپلم',
        'maalolyt' => 'خیر',
        'bymary_mzmn' => 'خیر',
        'dashtn_bymh' => 'بله',
        'noaa_bymh' => 'تأمین اجتماعی',
        'shrayt_khas' => '',
        'shghl' => 'کارگر',
        'odaayt_ashtghal' => 'بله',
        'mobayl' => '09123456789',
        'tlfn' => '02144445555'
    ],
    [
        'astan' => 'تهران',
        'shhr' => 'تهران', 
        'dhstan' => 'مرکزی',
        'adrs' => 'خیابان آزادی، کوچه نور، پلاک 15',
        'kd_psty' => '1234567890',
        'odaayt_mskn' => 'مستأجر',
        'todyhat_mskn' => 'آپارتمان 2 خوابه طبقه سوم',
        'nam' => 'فاطمه',
        'nam_khanoadgy' => 'احمدی',
        'kd_mly' => '9876543210',
        'nam_pdr' => 'علی',
        'tarykh_told' => '1365/03/10',
        'gnsyt' => 'زن',
        'nsbt_khanoadgy' => 'همسر',
        'odaayt_tahl' => 'متأهل',
        'thsylat' => 'لیسانس',
        'maalolyt' => 'خیر',
        'bymary_mzmn' => 'خیر',
        'dashtn_bymh' => 'بله',
        'noaa_bymh' => 'تأمین اجتماعی',
        'shrayt_khas' => '',
        'shghl' => 'خانه‌دار',
        'odaayt_ashtghal' => 'خیر',
        'mobayl' => '09198765432',
        'tlfn' => '02144445555'
    ]
];

print_r($testData); 