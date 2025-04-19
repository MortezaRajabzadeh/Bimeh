<?php

use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| روت‌های تلگرام 
|--------------------------------------------------------------------------
|
| این فایل برای تعریف روت‌های مربوط به وب‌هوک‌های تلگرام استفاده می‌شود.
| مسیرهای این فایل با پیشوند '/telegram' در دسترس خواهند بود.
|
*/

// دریافت و پردازش webhook های تلگرام
Route::post('/webhook', [TelegramController::class, 'handle']);

// تنظیم webhook تلگرام (فقط برای ادمین)
Route::get('/setup-webhook', [TelegramController::class, 'setupWebhook'])
    ->middleware('auth.admin');

// دریافت اطلاعات وضعیت فعلی ربات
Route::get('/bot-info', [TelegramController::class, 'getBotInfo'])
    ->middleware('auth.admin'); 