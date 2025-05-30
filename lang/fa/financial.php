<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Financial Report Messages (پیام‌های گزارش مالی)
    |--------------------------------------------------------------------------
    |
    | پیام‌ها و برچسب‌های مربوط به گزارش‌های مالی
    |
    */

    'titles' => [
        'financial_report' => 'گزارش مالی بیمه',
        'transactions' => 'لیست تراکنش‌ها',
        'import_reports' => 'گزارش ایمپورت فایل‌های اکسل',
        'account_balance' => 'موجودی حساب',
        'financial_status' => 'وضعیت مالی',
    ],

    'descriptions' => [
        'report_overview' => 'مشاهده کلیه تراکنش‌های مالی و موجودی حساب',
        'payment_for_families' => 'پرداخت حق بیمه برای :count خانواده به مبلغ :amount ریال',
        'payment_single_family' => 'پرداخت حق بیمه برای یک خانواده به مبلغ :amount ریال',
        'import_payment' => 'پرداخت حق بیمه از طریق ایمپورت اکسل',
    ],

    'transaction_types' => [
        'budget_allocation' => 'تخصیص بودجه',
        'premium_payment' => 'حق بیمه پرداختی',
        'premium_import' => 'بیمه پرداختی (ایمپورت اکسل)',
        'credit' => 'واریز',
        'debit' => 'برداشت',
    ],

    'statuses' => [
        'positive' => 'مثبت',
        'negative' => 'منفی',
    ],

    'actions' => [
        'view_families' => 'مشاهده لیست خانواده‌ها',
        'export_excel' => 'دانلود خروجی Excel',
        'view_details' => 'مشاهده جزئیات',
        'close' => 'بستن',
    ],

    'messages' => [
        'no_transactions' => 'هیچ تراکنشی ثبت نشده است',
        'no_import_reports' => 'هیچ گزارش ایمپورتی ثبت نشده است',
        'payment_details' => 'جزئیات پرداخت',
        'transaction_number' => 'تراکنش #:number',
        'total_transactions' => 'مجموع: :count تراکنش',
        'total_amount' => 'مجموع: :amount ریال',
        'showing_results' => 'نمایش :from تا :to از :total تراکنش',
        'showing_reports' => 'نمایش :from تا :to از :total گزارش',
        'per_page' => 'تعداد نمایش:',
    ],

    'table_headers' => [
        'transaction_description' => 'شرح تراکنش',
        'date' => 'تاریخ',
        'amount' => 'مبلغ',
        'type' => 'نوع',
        'user' => 'کاربر',
        'file_name' => 'نام فایل',
        'total_rows' => 'کل ردیف',
        'new' => 'جدید',
        'updated' => 'بروزرسانی',
        'unchanged' => 'بدون تغییر',
        'errors' => 'خطا',
        'families' => 'خانواده‌ها',
    ],

]; 