<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Status Colors Configuration
    |--------------------------------------------------------------------------
    |
    | تنظیمات رنگ‌بندی برای وضعیت‌های مختلف سیستم
    |
    */
    'status_colors' => [
        'complete' => [
            'color' => 'green',
            'bg_class' => 'bg-green-100',
            'text_class' => 'text-green-800',
            'border_class' => 'border-green-300',
            'icon_class' => 'text-green-600',
        ],
        
        'partial' => [
            'color' => 'yellow',
            'bg_class' => 'bg-yellow-100',
            'text_class' => 'text-yellow-800',
            'border_class' => 'border-yellow-300',
            'icon_class' => 'text-yellow-600',
        ],
        'warning' => [
            'color' => 'orange',
            'bg_class' => 'bg-orange-100',
            'text_class' => 'text-orange-800',
            'border_class' => 'border-orange-300',
            'icon_class' => 'text-orange-600',
        ],
        'incomplete' => [
            'color' => 'red',
            'bg_class' => 'bg-red-100',
            'text_class' => 'text-red-800',
            'border_class' => 'border-red-300',
            'icon_class' => 'text-red-600',
        ],
        'none' => [
            'color' => 'red',
            'bg_class' => 'bg-red-100',
            'text_class' => 'text-red-800',
            'border_class' => 'border-red-300',
            'icon_class' => 'text-red-600',
        ],
        'unknown' => [
            'color' => 'gray',
            'bg_class' => 'bg-gray-100',
            'text_class' => 'text-gray-800',
            'border_class' => 'border-gray-300',
            'icon_class' => 'text-gray-600',
        ],
        
    ],

    /*
    |--------------------------------------------------------------------------
    | Family Validation Icons
    |--------------------------------------------------------------------------
    |
    | تنظیمات آیکون‌های تایید وضعیت خانواده‌ها
    |
    */
    'family_validation_icons' => [
        'identity' => [
            'title' => 'اطلاعات هویتی',
            'description' => 'تکمیل بودن اطلاعات هویتی اعضای خانواده',
            'icon' => 'user-circle',
            'required_fields' => ['first_name', 'last_name', 'national_code', 'birth_date'],
        ],
        'location' => [
            'title' => 'وضعیت محرومیت منطقه',
            'description' => 'بررسی وضعیت محرومیت منطقه جغرافیایی خانواده',
            'icon' => 'location-marker',
        ],
        'documents' => [
            'title' => 'مدارک مورد نیاز',
            'description' => 'آپلود مدارک مربوط به شرایط خاص اعضای خانواده',
            'icon' => 'document-text',
            'document_types' => [
                'special_disease' => 'مدرک بیماری خاص',
                'disability' => 'مدرک معلولیت',
                'chronic_disease' => 'مدرک بیماری مزمن',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Thresholds
    |--------------------------------------------------------------------------
    |
    | آستانه‌های تعیین وضعیت (درصد)
    |
    */
    'validation_thresholds' => [
        'complete_min' => 100, // حداقل درصد برای وضعیت کامل
        'partial_min' => 30,   // حداقل درصد برای وضعیت جزئی
        // کمتر از partial_min = وضعیت none
    ],
]; 