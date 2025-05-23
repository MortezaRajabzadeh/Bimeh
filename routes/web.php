<?php

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Livewire\Auth\UserTypeSelection;
use App\Livewire\Auth\MicroLogin;
use App\Http\Middleware\CheckUserType;


// مسیرهای عمومی
Route::get('/', MicroLogin::class)->name('admin.login');

// صفحه انتخاب نوع کاربر
Route::get('/select-user-type', [\App\Http\Controllers\Auth\LoginController::class, 'showUserTypeSelection'])->name('select-user-type');

// صفحات لاگین هر نوع کاربر
Route::get('/charity/login', MicroLogin::class)->name('charity.login');
Route::get('/insurance/login', MicroLogin::class)->name('insurance.login');
Route::get('/admin/login', MicroLogin::class)->name('admin.login');
Route::get('/logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('logs.index');
Route::get('/logs/{activity}', [\App\Http\Controllers\Admin\ActivityLogController::class, 'show'])->name('logs.show');

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

// مسیرهای ادمین سیستم
Route::middleware(['auth', 'verified', CheckUserType::class.':admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');
    
    // مدیریت سازمان‌ها
    Route::middleware('can:view organizations')->get('/organizations', [\App\Http\Controllers\Admin\OrganizationController::class, 'index'])->name('organizations.index');
    Route::middleware('can:view organizations')->get('/organizations/{organization}', [\App\Http\Controllers\Admin\OrganizationController::class, 'show'])->name('organizations.show');
    Route::middleware('can:create organization')->get('/organizations/create', [\App\Http\Controllers\Admin\OrganizationController::class, 'create'])->name('organizations.create');
    Route::middleware('can:create organization')->post('/organizations', [\App\Http\Controllers\Admin\OrganizationController::class, 'store'])->name('organizations.store');
    Route::middleware('can:edit organization')->get('/organizations/{organization}/edit', [\App\Http\Controllers\Admin\OrganizationController::class, 'edit'])->name('organizations.edit');
    Route::middleware('can:edit organization')->put('/organizations/{organization}', [\App\Http\Controllers\Admin\OrganizationController::class, 'update'])->name('organizations.update');
    Route::middleware('can:delete organization')->delete('/organizations/{organization}', [\App\Http\Controllers\Admin\OrganizationController::class, 'destroy'])->name('organizations.destroy');
    
    // مدیریت مناطق
    Route::middleware('can:view regions')->get('/regions', [\App\Http\Controllers\Admin\RegionController::class, 'index'])->name('regions.index');
    Route::middleware('can:view regions')->get('/regions/{region}', [\App\Http\Controllers\Admin\RegionController::class, 'show'])->name('regions.show');
    Route::middleware('can:create region')->get('/regions/create', [\App\Http\Controllers\Admin\RegionController::class, 'create'])->name('regions.create');
    Route::middleware('can:create region')->post('/regions', [\App\Http\Controllers\Admin\RegionController::class, 'store'])->name('regions.store');
    Route::middleware('can:edit region')->get('/regions/{region}/edit', [\App\Http\Controllers\Admin\RegionController::class, 'edit'])->name('regions.edit');
    Route::middleware('can:edit region')->put('/regions/{region}', [\App\Http\Controllers\Admin\RegionController::class, 'update'])->name('regions.update');
    Route::middleware('can:delete region')->delete('/regions/{region}', [\App\Http\Controllers\Admin\RegionController::class, 'destroy'])->name('regions.destroy');
    
    // مدیریت کاربران
    Route::middleware('can:view users')->get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::middleware('can:view users')->get('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');
    Route::middleware('can:create user')->get('/users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create');
    Route::middleware('can:create user')->post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::middleware('can:edit user')->get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit');
    Route::middleware('can:edit user')->put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::middleware('can:delete user')->delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
    
    // گزارش‌ها
    Route::middleware('can:view reports')->get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
    Route::middleware('can:export reports')->get('/reports/export', [\App\Http\Controllers\Admin\ReportController::class, 'export'])->name('reports.export');
    
    // لاگ فعالیت‌ها
    Route::middleware('can:view activity logs')->get('/logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('logs.index');
    Route::middleware('can:view activity logs')->get('/logs/{activity}', [\App\Http\Controllers\Admin\ActivityLogController::class, 'show'])->name('logs.show');
});

// مسیرهای خیریه
Route::middleware(['auth', 'verified', CheckUserType::class.':charity'])->prefix('charity')->name('charity.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Charity\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/insured-families', [App\Http\Controllers\Charity\DashboardController::class, 'insuredFamilies'])->name('insured-families');
    Route::get('/uninsured-families', [App\Http\Controllers\Charity\DashboardController::class, 'uninsuredFamilies'])->name('uninsured-families');
    Route::get('/add-family', [App\Http\Controllers\Charity\DashboardController::class, 'addFamily'])->name('add-family');
    
    // جستجوی خانواده‌ها - استفاده می‌شد قبل از طراحی لایوویر
    Route::get('/search', [App\Http\Controllers\Charity\FamilyController::class, 'search'])->name('search');
    
    // مدیریت خانواده‌ها
    Route::middleware('can:view families')->get('/families', [\App\Http\Controllers\Charity\FamilyController::class, 'index'])->name('families.index');
    Route::middleware('can:view families')->get('/families/{family}', [\App\Http\Controllers\Charity\FamilyController::class, 'show'])->name('families.show');
    Route::middleware('can:create family')->get('/families/create', [\App\Http\Controllers\Charity\FamilyController::class, 'create'])->name('families.create');
    Route::middleware('can:create family')->post('/families', [\App\Http\Controllers\Charity\FamilyController::class, 'store'])->name('families.store');
    Route::middleware('can:edit family')->get('/families/{family}/edit', [\App\Http\Controllers\Charity\FamilyController::class, 'edit'])->name('families.edit');
    Route::middleware('can:edit family')->put('/families/{family}', [\App\Http\Controllers\Charity\FamilyController::class, 'update'])->name('families.update');
    Route::middleware('can:delete family')->delete('/families/{family}', [\App\Http\Controllers\Charity\FamilyController::class, 'destroy'])->name('families.destroy');
    
    // مدیریت اعضای خانواده
    Route::middleware('can:view members')->get('/families/{family}/members', [\App\Http\Controllers\Charity\MemberController::class, 'index'])->name('families.members.index');
    Route::middleware('can:create member')->get('/families/{family}/members/create', [\App\Http\Controllers\Charity\MemberController::class, 'create'])->name('families.members.create');
    Route::middleware('can:create member')->post('/families/{family}/members', [\App\Http\Controllers\Charity\MemberController::class, 'store'])->name('families.members.store');
    Route::middleware('can:edit member')->get('/families/{family}/members/{member}/edit', [\App\Http\Controllers\Charity\MemberController::class, 'edit'])->name('families.members.edit');
    Route::middleware('can:edit member')->put('/families/{family}/members/{member}', [\App\Http\Controllers\Charity\MemberController::class, 'update'])->name('families.members.update');
    Route::middleware('can:delete member')->delete('/families/{family}/members/{member}', [\App\Http\Controllers\Charity\MemberController::class, 'destroy'])->name('families.members.destroy');
    
    // آپلود اکسل خانواده‌ها
    Route::middleware('can:create family')->get('/import', [\App\Http\Controllers\Charity\ImportController::class, 'index'])->name('import.index');
    Route::middleware('can:create family')->post('/import', [\App\Http\Controllers\Charity\ImportController::class, 'import'])->name('import.store');
    Route::middleware('can:create family')->get('/import/template', [\App\Http\Controllers\Charity\ImportController::class, 'downloadTemplate'])->name('import.template');

    Route::get('/export-excel', [App\Http\Controllers\Charity\FamilyController::class, 'exportExcel'])->name('export-excel');
});

// مسیرهای بیمه
Route::middleware(['auth', 'verified', CheckUserType::class.':insurance'])->prefix('insurance')->name('insurance.')->group(function () {
    Route::get('/dashboard', function () {
        return view('insurance.dashboard');
    })->name('dashboard');
    
    // مشاهده و تایید خانواده‌ها
    Route::middleware('can:view families')->get('/families', [\App\Http\Controllers\Insurance\FamilyController::class, 'index'])->name('families.index');
    Route::middleware('can:view families')->get('/families/{family}', [\App\Http\Controllers\Insurance\FamilyController::class, 'show'])->name('families.show');
    Route::middleware('can:change family status')->put('/families/{family}/status', [\App\Http\Controllers\Insurance\FamilyController::class, 'updateStatus'])->name('families.update-status');
    
    // گزارش‌ها
    Route::middleware('can:view reports')->get('/reports', [\App\Http\Controllers\Insurance\ReportController::class, 'index'])->name('reports.index');
    Route::middleware('can:export reports')->get('/reports/export', [\App\Http\Controllers\Insurance\ReportController::class, 'export'])->name('reports.export');
});

require __DIR__.'/auth.php';
