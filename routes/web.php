<?php

use App\Http\Controllers\OtpLoginController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::prefix('otp')->name('otp.')->group(function () {
    Route::post('/send', [OtpLoginController::class, 'send'])->name('send');
    Route::post('/verify', [OtpLoginController::class, 'verify'])->name('verify');
});

Route::prefix('payment')->name('payment.')->middleware('auth')->group(function () {
    Route::post('/request', [PaymentController::class, 'request'])->name('request');
    Route::get('/callback', [PaymentController::class, 'callback'])->name('callback');
});

// مسیرهای عمومی
Route::view('/', 'welcome');

// صفحه ورود با OTP
Volt::route('login/otp', 'pages.auth.otp-login')
    ->name('login.otp')
    ->middleware('guest');

// صفحه خانه (داشبورد)
Volt::route('home', 'pages.home')
    ->name('home')
    ->middleware('auth');

// مسیرهای پیش‌فرض برنامه
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
