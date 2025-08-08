<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Livewire\Volt\Volt;
use App\Livewire\Auth\UserTypeSelection;
use App\Livewire\Auth\MicroLogin;
use App\Http\Middleware\CheckUserType;
use App\Livewire\Examples\ToastExample;
use App\Http\Controllers\FamilyDownloadController;
use App\Http\Controllers\PaymentController;

// Health Check Route for Liara
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'app' => config('app.name'),
        'version' => '1.0.0'
    ]);
})->name('health');

// مسیرهای عمومی
Route::get('/', MicroLogin::class)->name('home.login');

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
    Route::middleware('can:view dashboard')->get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::post('/impersonate-user', [\App\Http\Controllers\Admin\UserImpersonationController::class, 'store'])->name('impersonate-user.store');
    Route::post('/stop-impersonating-user', [\App\Http\Controllers\Admin\UserImpersonationController::class, 'stop'])->name('stop-impersonating-user');
    // تعویض نقش ادمین (جدید)
    Route::post('/switch-role', [\App\Http\Controllers\Admin\SwitchRoleController::class, 'store'])->name('switch-role.store');

    // تنظیمات سیستم
    Route::middleware('can:manage system settings')->get('/settings', function () {
        return view('admin.settings');
    })->name('settings');

    // مدیریت مناطق محروم
    Route::middleware('can:view advanced reports')->get('/regions', function () {
        return view('insurance.deprived-areas');
    })->name('regions.index');

    // مدیریت کاربران
    Route::middleware('can:manage users')->get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('users.index');
    Route::middleware('can:manage users')->get('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'show'])->name('users.show');
    Route::middleware('can:manage users')->get('/users/create', [\App\Http\Controllers\Admin\UserController::class, 'create'])->name('users.create');
    Route::middleware('can:manage users')->post('/users', [\App\Http\Controllers\Admin\UserController::class, 'store'])->name('users.store');
    Route::middleware('can:manage users')->get('/users/{user}/edit', [\App\Http\Controllers\Admin\UserController::class, 'edit'])->name('users.edit');
    Route::middleware('can:manage users')->put('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'update'])->name('users.update');
    Route::middleware('can:manage users')->delete('/users/{user}', [\App\Http\Controllers\Admin\UserController::class, 'destroy'])->name('users.destroy');
    Route::middleware('can:manage users')->delete('/users', [\App\Http\Controllers\Admin\UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');

    // گزارش‌ها
    Route::middleware('can:view all statistics')->get('/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
    Route::middleware('can:export reports')->get('/reports/export', [\App\Http\Controllers\Admin\ReportController::class, 'export'])->name('reports.export');

    // لاگ فعالیت‌ها
    Route::middleware('can:view system logs')->get('/logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index'])->name('logs.index');
    Route::middleware('can:view system logs')->get('/logs/{activity}', [\App\Http\Controllers\Admin\ActivityLogController::class, 'show'])->name('logs.show');

    // مدیریت سازمان‌ها
    Route::middleware('can:manage organizations')->resource('organizations', \App\Http\Controllers\Admin\OrganizationController::class);
    Route::middleware('can:manage organizations')->delete('organizations', [\App\Http\Controllers\Admin\OrganizationController::class, 'bulkDestroy'])->name('organizations.bulk-destroy');

    // مدیریت سطوح دسترسی
    Route::middleware('can:manage roles')->resource('access-levels', \App\Http\Controllers\RoleManagementController::class);

    // مدیریت نقش‌ها (جدید)
    Route::middleware('can:manage roles')->resource('roles', \App\Http\Controllers\RoleManagementController::class);

    // مدیریت نقش‌های سفارشی
    Route::middleware('can:manage roles')->resource('custom-roles', \App\Http\Controllers\CustomRoleController::class);
    Route::middleware('can:manage roles')->get('/custom-roles/{customRole}/permissions', [\App\Http\Controllers\CustomRoleController::class, 'permissions'])->name('custom-roles.permissions');
    Route::middleware('can:manage roles')->post('/custom-roles/{customRole}/permissions', [\App\Http\Controllers\CustomRoleController::class, 'updatePermissions'])->name('custom-roles.update-permissions');
});

// مسیرهای خیریه
Route::middleware(['auth', 'verified', CheckUserType::class.':charity'])->prefix('charity')->name('charity.')->group(function () {
    Route::middleware('can:view dashboard')->get('/dashboard', [App\Http\Controllers\Charity\DashboardController::class, 'index'])->name('dashboard');
    Route::middleware('can:view own families')->get('/insured-families', [App\Http\Controllers\Charity\DashboardController::class, 'insuredFamilies'])->name('insured-families');
    Route::middleware('can:view own families')->get('/uninsured-families', [App\Http\Controllers\Charity\DashboardController::class, 'uninsuredFamilies'])->name('uninsured-families');
    Route::middleware('can:create family')->get('/add-family', [App\Http\Controllers\Charity\DashboardController::class, 'addFamily'])->name('add-family');

    // مسیر آپلود فایل اکسل خانواده‌ها
    Route::middleware('can:create family')->post('/families/import', [\App\Http\Controllers\Charity\ImportController::class, 'import'])->name('families.import');

    // جستجوی خانواده‌ها
    Route::middleware('can:view own families')->get('/search', [App\Http\Controllers\Charity\FamilyController::class, 'search'])->name('search');

    // مدیریت خانواده‌ها
    Route::middleware('can:view own families')->get('/families', [\App\Http\Controllers\Charity\FamilyController::class, 'index'])->name('families.index');
    Route::middleware('can:view own families')->get('/families/{family}', [\App\Http\Controllers\Charity\FamilyController::class, 'show'])->name('families.show');
    Route::middleware('can:create family')->get('/families/create', [\App\Http\Controllers\Charity\FamilyController::class, 'create'])->name('families.create');
    Route::middleware('can:create family')->post('/families', [\App\Http\Controllers\Charity\FamilyController::class, 'store'])->name('families.store');
    Route::middleware('can:edit own family')->get('/families/{family}/edit', [\App\Http\Controllers\Charity\FamilyController::class, 'edit'])->name('families.edit');
    Route::middleware('can:edit own family')->put('/families/{family}', [\App\Http\Controllers\Charity\FamilyController::class, 'update'])->name('families.update');
    Route::middleware('can:delete own family')->delete('/families/{family}', [\App\Http\Controllers\Charity\FamilyController::class, 'destroy'])->name('families.destroy');

    // مدیریت اعضای خانواده
    Route::middleware('can:view family members')->get('/families/{family}/members', [\App\Http\Controllers\Charity\MemberController::class, 'index'])->name('families.members.index');
    Route::middleware('can:add family member')->get('/families/{family}/members/create', [\App\Http\Controllers\Charity\MemberController::class, 'create'])->name('families.members.create');
    Route::middleware('can:add family member')->post('/families/{family}/members', [\App\Http\Controllers\Charity\MemberController::class, 'store'])->name('families.members.store');
    Route::middleware('can:edit family member')->get('/families/{family}/members/{member}/edit', [\App\Http\Controllers\Charity\MemberController::class, 'edit'])->name('families.members.edit');
    Route::middleware('can:edit family member')->put('/families/{family}/members/{member}', [\App\Http\Controllers\Charity\MemberController::class, 'update'])->name('families.members.update');
    Route::middleware('can:remove family member')->delete('/families/{family}/members/{member}', [\App\Http\Controllers\Charity\MemberController::class, 'destroy'])->name('families.members.destroy');

    // آپلود اکسل خانواده‌ها
    Route::middleware('can:create family')->get('/import', [\App\Http\Controllers\Charity\ImportController::class, 'index'])->name('import.index');
    Route::middleware('can:create family')->post('/import', [\App\Http\Controllers\Charity\ImportController::class, 'import'])->name('import.store');
    Route::middleware('can:create family')->get('/import/template', [\App\Http\Controllers\Charity\ImportController::class, 'downloadTemplate'])->name('import.template');
    Route::middleware('can:create family')->get('/import/template/families', [\App\Http\Controllers\Charity\ImportController::class, 'downloadFamiliesTemplate'])->name('import.template.families');
    Route::middleware('can:create family')->get('/import/status', [\App\Http\Controllers\Charity\ImportController::class, 'checkJobStatus'])->name('import.status');

    Route::middleware('can:export reports')->get('/export-excel', [App\Http\Controllers\Charity\FamilyController::class, 'exportExcel'])->name('export-excel');

    Route::middleware('can:view profile')->get('/settings', function () {
        return view('charity.settings.index');
    })->name('settings');

    Route::middleware('can:view profile')->post('/settings/update', [\App\Http\Controllers\Charity\SettingsController::class, 'update'])->name('settings.update');
});

// مسیرهای آپلود مدارک اعضای خانواده (خارج از تمام گروه‌ها)
Route::middleware(['auth', 'verified', 'can:edit family member'])->group(function () {
    // مسیر family.members.documents.upload
    Route::get('/family/{family}/members/{member}/documents/upload', [\App\Http\Controllers\Charity\MemberDocumentController::class, 'showUploadForm'])->name('family.members.documents.upload');
    Route::post('/family/{family}/members/{member}/documents/upload', [\App\Http\Controllers\Charity\MemberDocumentController::class, 'store'])->name('family.members.documents.store');

    // مسیر charity.family.members.documents.upload
    Route::get('/charity-routes/family/{family}/members/{member}/documents/upload', [\App\Http\Controllers\Charity\MemberDocumentController::class, 'showUploadForm'])->name('charity.family.members.documents.upload');
    Route::post('/charity-routes/family/{family}/members/{member}/documents/upload', [\App\Http\Controllers\Charity\MemberDocumentController::class, 'store'])->name('charity.family.members.documents.store');
});

// مسیر نمایش فایل‌های آپلود شده (به صورت مستقل)
Route::middleware(['auth', 'verified'])->get('/family/{family}/members/{member}/documents/{collection}/{media}', [\App\Http\Controllers\Charity\MemberDocumentController::class, 'showDocument'])->name('family.members.documents.show');

// Route موقتی برای انتقال لوگوها به مسیر جدید
Route::get('/migrate-logos-temp', function () {
    $oldPath = storage_path('app/public/organizations/logos/688de491e14a3.webp');
    $newDir = public_path('images/organizations/logos');
    $newPath = $newDir . '/688de491e14a3.webp';
    $relativePath = 'images/organizations/logos/688de491e14a3.webp';
    
    $result = [];
    
    // ایجاد دایرکتوری جدید
    if (!is_dir($newDir)) {
        File::makeDirectory($newDir, 0755, true);
        $result[] = 'دایرکتوری جدید ایجاد شد: ' . $newDir;
    }
    
    // کپی فایل
    if (file_exists($oldPath)) {
        if (File::copy($oldPath, $newPath)) {
            $result[] = 'فایل با موفقیت کپی شد: ' . $newPath;
            
            // بروزرسانی دیتابیس
            try {
                $updated = DB::table('organizations')
                    ->where('logo_path', 'organizations/logos/688de491e14a3.webp')
                    ->update(['logo_path' => $relativePath]);
                
                if ($updated > 0) {
                    $result[] = 'دیتابیس بروزرسانی شد (' . $updated . ' رکورد)';
                    
                    // بررسی نهایی
                    $org = DB::table('organizations')->where('logo_path', $relativePath)->first();
                    if ($org) {
                        $result[] = 'رکورد پیدا شد - ID: ' . $org->id;
                        $result[] = 'مسیر جدید: ' . $org->logo_path;
                        $result[] = 'URL جدید: ' . asset($relativePath);
                    }
                } else {
                    $result[] = 'هیچ رکوردی برای بروزرسانی پیدا نشد';
                }
                
            } catch (Exception $e) {
                $result[] = 'خطا در بروزرسانی دیتابیس: ' . $e->getMessage();
            }
            
        } else {
            $result[] = 'خطا در کپی فایل';
        }
    } else {
        $result[] = 'فایل اصلی پیدا نشد: ' . $oldPath;
    }
    
    return response()->json([
        'success' => true,
        'results' => $result
    ], 200, [], JSON_UNESCAPED_UNICODE);
});

// مسیرهای بیمه
Route::middleware(['auth', 'verified', CheckUserType::class.':insurance'])->prefix('insurance')->name('insurance.')->group(function () {
    Route::middleware('can:view dashboard')->get('/dashboard', function () {
        return view('insurance.dashboard');
    })->name('dashboard');

    Route::middleware('can:view advanced reports')->get('/deprived-areas', function () {
        return view('insurance.deprived-areas');
    })->name('deprived-areas');

    Route::redirect('/families', '/insurance/families/approval');
    Route::middleware('can:view all families')->get('/families/list', [\App\Http\Controllers\Insurance\FamilyController::class, 'listByCodes'])->name('families.list');

    Route::middleware('can:view all families')->get('/families/approval', function () {
        return view('insurance.families-approval');
    })->name('families.approval');

    Route::middleware('can:view all families')->get('/families/{family}', [\App\Http\Controllers\Insurance\FamilyController::class, 'show'])->name('families.show');
    Route::middleware('can:change family status')->put('/families/{family}/status', [\App\Http\Controllers\Insurance\FamilyController::class, 'updateStatus'])->name('families.update-status');

    // گزارش‌ها
    Route::middleware('can:view advanced reports')->get('/reports', [\App\Http\Controllers\Insurance\ReportController::class, 'index'])->name('reports.index');
    Route::middleware('can:export reports')->get('/reports/export', [\App\Http\Controllers\Insurance\ReportController::class, 'export'])->name('reports.export');

    Route::middleware('can:view profile')->get('/settings', [\App\Http\Controllers\Insurance\SettingsController::class, 'index'])->name('settings');
    Route::middleware('can:view profile')->get('/settings/general', [\App\Http\Controllers\Insurance\SettingsController::class, 'general'])->name('settings.general');
    Route::middleware('can:view profile')->post('/settings/general', [\App\Http\Controllers\Insurance\SettingsController::class, 'update'])->name('settings.update');

    
    Route::middleware('can:view claims history')->get('/paid-claims', function () {
        return view('insurance.paid-claims');
    })->name('paid-claims');
    
    Route::middleware('can:view claims history')->get('/claims-summary', function () {
        return view('insurance.claims-summary');
    })->name('claims-summary');

    Route::middleware('can:manage insurance policies')->get('/funding-manager', function () {
        return view('insurance.funding-manager');
    })->name('funding-manager');

    Route::middleware('can:view all families')->get('/insured-families', function () {
        return view('insurance.insured-families');
    })->name('insured-families');

    Route::middleware('can:view all families')->get('/uninsured-families', function () {
        return view('insurance.uninsured-families');
    })->name('uninsured-families');

    // مسیرهای مدیریت تخصیص بودجه
    Route::middleware('can:manage insurance policies')->get('/allocations', [\App\Http\Controllers\FamilyFundingAllocationController::class, 'index'])->name('allocations.index');
    Route::middleware('can:manage insurance policies')->get('/allocations/create', [\App\Http\Controllers\FamilyFundingAllocationController::class, 'create'])->name('allocations.create');
    Route::middleware('can:manage insurance policies')->post('/allocations', [\App\Http\Controllers\FamilyFundingAllocationController::class, 'store'])->name('allocations.store');
    Route::middleware('can:manage insurance policies')->get('/allocations/{allocation}', [\App\Http\Controllers\FamilyFundingAllocationController::class, 'show'])->name('allocations.show');
    Route::middleware('can:manage insurance policies')->get('/allocations/{allocation}/edit', [\App\Http\Controllers\FamilyFundingAllocationController::class, 'edit'])->name('allocations.edit');
    Route::middleware('can:manage insurance policies')->put('/allocations/{allocation}', [\App\Http\Controllers\FamilyFundingAllocationController::class, 'update'])->name('allocations.update');
    Route::middleware('can:manage insurance policies')->delete('/allocations/{allocation}', [\App\Http\Controllers\FamilyFundingAllocationController::class, 'destroy'])->name('allocations.destroy');
    Route::middleware('can:manage insurance policies')->post('/allocations/{allocation}/approve', [\App\Http\Controllers\FamilyFundingAllocationController::class, 'approve'])->name('allocations.approve');
    Route::middleware('can:manage insurance policies')->post('/allocations/{allocation}/mark-as-paid', [\App\Http\Controllers\FamilyFundingAllocationController::class, 'markAsPaid'])->name('allocations.mark-as-paid');
    Route::middleware('can:manage insurance policies')->get('/allocations/family/{familyId}', [\App\Http\Controllers\FamilyFundingAllocationController::class, 'familyReport'])->name('allocations.family-report');

    // گزارش مالی
    Route::middleware('can:view advanced reports')->get('/financial-report', [\App\Http\Controllers\Insurance\FinancialReportController::class, 'index'])->name('financial-report');
    Route::middleware('can:view advanced reports')->get('/financial-report/export', [\App\Http\Controllers\Insurance\FinancialReportController::class, 'exportExcel'])->name('financial-report.export');
    Route::middleware('can:view advanced reports')->get('/financial-report/payment/{paymentId}', [\App\Http\Controllers\Insurance\FinancialReportController::class, 'paymentDetails'])->name('financial-report.payment-details');

    // مدیریت سهم‌بندی حق بیمه
    Route::middleware('can:manage insurance policies')->resource('shares', \App\Http\Controllers\InsuranceShareController::class);
    Route::middleware('can:manage insurance policies')->get('/family-insurances/{familyInsurance}/shares', [\App\Http\Controllers\InsuranceShareController::class, 'byFamilyInsurance'])->name('family-insurances.shares');
    Route::middleware('can:manage insurance policies')->post('/shares/{share}/mark-paid', [\App\Http\Controllers\InsuranceShareController::class, 'markAsPaid'])->name('shares.mark-paid');

    // صفحه مدیریت سهم‌بندی Real-Time
    Route::middleware('can:manage insurance shares')->get('/shares-manager', [\App\Http\Controllers\InsuranceShareController::class, 'manage'])->name('shares.manage');

    // مدیریت پرداخت‌های بیمه
    Route::middleware('can:view advanced reports')->resource('payments', \App\Http\Controllers\InsurancePaymentController::class);

    // روت جزئیات سهم‌بندی بیمه - باید بعد از تعریف resource باشد و با نام متفاوت
    Route::get('/share-details/{share}', [App\Http\Controllers\Insurance\FinancialReportController::class, 'shareDetails'])
        ->name('shares.details');


    // مسیرهای بخش بیمه
    Route::prefix('families')->name('families.')->group(function () {
        // مسیر به‌روزرسانی دسته‌ای وضعیت خانواده‌ها
        Route::post('/bulk-update-status', [App\Http\Controllers\Insurance\FamilyStatusController::class, 'bulkUpdateStatus'])
            ->name('bulk-update-status');

    });


    // مسیر به‌روزرسانی دسته‌ای وضعیت خانواده‌ها
    Route::post('/families/bulk-update-status', [App\Http\Controllers\Api\InsuranceFamilyController::class, 'bulkUpdateStatus'])
        ->name('families.bulk-update-status');

    // مسیر مدیریت منابع تأمین مالی
    Route::middleware('can:manage insurance policies')->resource('funding-sources', \App\Http\Controllers\FundingSourceController::class);
});

Route::get('/families/download', [FamilyDownloadController::class, 'download'])
    ->name('families.download-route')
    ->middleware(['auth', 'signed']);

Route::post('/families/download-excel', [App\Http\Controllers\FamilyController::class, 'downloadExcel'])
->name('families.download-excel')
->middleware('auth');
// مسیر تست اعلان‌های توست
Route::get('/examples/toast-test', ToastExample::class)->name('examples.toast-test');

require __DIR__.'/auth.php';
