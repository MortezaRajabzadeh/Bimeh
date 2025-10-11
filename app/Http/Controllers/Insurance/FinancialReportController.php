<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Services\FinancialReportService;
use App\Helpers\FinancialCacheHelper;
use App\Jobs\ProcessFinancialReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FinancialReportExport;
use Illuminate\Support\Facades\Storage;

class FinancialReportController extends Controller
{
    private FinancialReportService $financialReportService;
    protected FinancialCacheHelper $cacheHelper;

    public function __construct(FinancialReportService $financialReportService, FinancialCacheHelper $cacheHelper = null)
    {
        $this->financialReportService = $financialReportService;
        $this->cacheHelper = $cacheHelper ?? new FinancialCacheHelper();
    }
    /**
     * نمایش صفحه گزارش مالی - استفاده از Service
     */
    public function index(Request $request)
    {
        try {
            // دریافت تراکنش‌ها با استفاده از Service
            $transactionsPaginated = $this->financialReportService->getTransactionsWithPagination($request);
            
            // محاسبه خلاصه مالی
            $allTransactions = $this->financialReportService->getAllTransactions();
            $summary = $this->financialReportService->calculateSummary($allTransactions);
            $balance = $summary['balance'];
            
            // گزارش ایمپورت‌های اکسل (به همان شکل قبلی)
            $logs = \App\Models\InsuranceImportLog::with('user')
                ->orderByDesc('created_at')
                ->paginate(20, ['*'], 'logs_page');
                
            $totalAmount = \App\Models\InsuranceImportLog::sum('total_insurance_amount');

            // اطلاعات خانواده‌های بیمه شده برای نمایش در گزارش
            $insuredFamilies = \App\Models\Family::whereHas('insurances', function($query) {
                $query->where('status', 'active');
            })->with(['insurances' => function($query) {
                $query->where('status', 'active')->with('shares');
            }, 'members'])
            ->paginate(10, ['*'], 'families_page');

            return view('insurance.financial-report', compact(
                'transactionsPaginated',
                'balance', 
                'logs', 
                'totalAmount', 
                'insuredFamilies'
            ));
            
        } catch (\Exception $e) {
            return back()->with('error', 'خطا در بارگذاری گزارش مالی: ' . $e->getMessage());
        }
    }

    /**
     * صدور گزارش اکسل
     */
    public function exportExcel(Request $request)
    {
        try {
            // اعتبارسنجی ورودی‌ها
            $data = $request->validate([
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'type' => 'nullable|in:credit,debit,all',
                'format' => 'nullable|in:xlsx,csv'
            ]);
            
            // محاسبه تعداد تقریبی تراکنش‌ها
            $estimatedCount = $this->financialReportService->getTransactionsCount($data);
            if ($estimatedCount > 10000) {
                return back()->with('error',
                    'تعداد تراکنش‌ها بیش از حد مجاز است (' . number_format($estimatedCount) . ' رکورد). ' .
                    'لطفاً بازه زمانی کوچک‌تری انتخاب کنید.'
                );
            }
            
            // محدودیت بازه زمانی
            if (isset($data['from_date']) && isset($data['to_date'])) {
                $fromDate = \Carbon\Carbon::parse($data['from_date']);
                $toDate = \Carbon\Carbon::parse($data['to_date']);
                $daysDiff = $fromDate->diffInDays($toDate);
                
                if ($daysDiff > 365) {
                    return back()->with('error', 
                        'بازه زمانی نمی‌تواند بیشتر از یک سال باشد. ' .
                        'بازه انتخابی: ' . $daysDiff . ' روز'
                    );
                }
            }

            // بررسی دسترسی کاربر
            if (!auth()->user()->can('view advanced reports')) {
                return back()->with('error', 'شما دسترسی لازم برای دانلود گزارش را ندارید.');
            }

            // بررسی وجود کلاس Export
            if (!class_exists(\App\Exports\FinancialReportExport::class)) {
                return back()->with('error', 'کلاس صدور گزارش یافت نشد. لطفاً با مدیر سیستم تماس بگیرید.');
            }

            // Dispatch Job برای export async
            $format = $data['format'] ?? 'xlsx';
            
            $job = new ProcessFinancialReportExport(auth()->user(), $data, $format);
            $jobId = $job->getJobId();
            
            dispatch($job);
            
            \Illuminate\Support\Facades\Log::info('📤 Job export گزارش مالی dispatch شد', [
                'job_id' => $jobId,
                'user_id' => auth()->id(),
                'estimated_records' => $estimatedCount,
                'format' => $format
            ]);
            
            // اگر درخواست AJAX است، JSON برگردان
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'export_job_id' => $jobId,
                    'message' => 'درخواست export شما در حال پردازش است. پس از اتمام، فایل برای دانلود آماده می‌شود.',
                    'estimated_records' => $estimatedCount
                ]);
            }
            
            return back()->with('success', 'درخواست export شما در حال پردازش است. پس از اتمام، فایل برای دانلود آماده می‌شود.')
                  ->with('export_job_id', $jobId);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->validator)
                ->with('error', 'داده‌های ورودی نامعتبر است.');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return back()->with('error', 'شما دسترسی لازم برای این عملیات را ندارید.');

        } catch (\Maatwebsite\Excel\Exceptions\LaravelExcelException $e) {
            return back()->with('error', 'خطا در تولید فایل اکسل: ' . $e->getMessage());

        } catch (\Exception $e) {
            // نمایش پیام خطا به کاربر
            return back()->with('error', 'خطا در تولید گزارش: ' . $e->getMessage());
        }
    }
    
    /**
     * بررسی وضعیت export job
     */
    public function checkExportStatus(Request $request)
    {
        $jobId = $request->get('job_id');
        
        if (!$jobId) {
            return response()->json(['error' => 'Job ID is required'], 400);
        }
        
        $cacheKey = "financial_export_job_{$jobId}";
        $status = Cache::get($cacheKey);
        
        if (!$status) {
            return response()->json(['error' => 'Job not found'], 404);
        }
        
        // بررسی امنیت: فقط کاربر صاحب job
        if ($status['user_id'] !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        return response()->json($status);
    }
    
    /**
     * دانلود فایل export شده
     */
    public function downloadExportedFile(Request $request, string $jobId)
    {
        $cacheKey = "financial_export_job_{$jobId}";
        $status = Cache::get($cacheKey);
        
        if (!$status) {
            abort(404, 'فایل export یافت نشد');
        }
        
        // بررسی امنیت: فقط کاربر صاحب job
        if ($status['user_id'] !== auth()->id()) {
            abort(403, 'شما دسترسی لازم برای دانلود این فایل را ندارید');
        }
        
        // بررسی وضعیت completed
        if ($status['status'] !== 'completed') {
            abort(400, 'فایل هنوز آماده نیست');
        }
        
        $filePath = $status['results']['file_path'] ?? null;
        
        if (!$filePath || !Storage::disk('public')->exists($filePath)) {
            abort(404, 'فایل در سرور یافت نشد');
        }
        
        \Illuminate\Support\Facades\Log::info('📎 دانلود فایل export', [
            'job_id' => $jobId,
            'user_id' => auth()->id(),
            'file_path' => $filePath,
            'file_size' => $status['results']['file_size'] ?? 0
        ]);
        
        return Storage::disk('public')->download($filePath, $status['results']['filename'] ?? 'financial_report.xlsx');
    }

    /**
     * نمایش جزئیات پرداخت
     */
    public function paymentDetails(Request $request, $paymentId)
    {
        $type = $request->get('type', 'allocation'); // allocation, payment, import, family_funding

        switch ($type) {
            case 'allocation':
                $payment = \App\Models\InsuranceAllocation::with(['family.members'])->findOrFail($paymentId);
                $families = collect([$payment->family]);
                break;

            case 'payment':
                $payment = \App\Models\InsurancePayment::with(['familyInsurance.family', 'details.member'])->findOrFail($paymentId);
                $families = collect([$payment->familyInsurance->family]);
                break;

            case 'import':
                $payment = \App\Models\InsuranceImportLog::findOrFail($paymentId);
                $familyCodes = array_merge(
                    is_array($payment->created_family_codes) ? $payment->created_family_codes : [],
                    is_array($payment->updated_family_codes) ? $payment->updated_family_codes : []
                );
                $families = \App\Models\Family::whereIn('family_code', $familyCodes)->with('members')->get();
                break;

            case 'family_funding':
                $payment = \App\Models\FamilyFundingAllocation::with(['family.members', 'fundingSource'])->findOrFail($paymentId);
                $families = collect([$payment->family]);
                break;

            default:
                abort(404);
        }

        return view('insurance.payment-details', compact('families', 'type', 'paymentId', 'payment'));
    }

    public function importLogs()
    {
        $logs = \App\Models\InsuranceImportLog::with('user')
            ->orderByDesc('created_at')
            ->paginate(20);
        $totalAmount = \App\Models\InsuranceImportLog::sum('total_insurance_amount');
        return view('insurance.financial-report', compact('logs', 'totalAmount'));
    }

    /**
     * نمایش جزئیات تخصیص بیمه
     */
    public function shareDetails($shareId)
    {
        $share = \App\Models\InsuranceShare::with(['familyInsurance.family.members', 'fundingSource', 'creator'])
            ->findOrFail($shareId);

        $family = $share->familyInsurance->family;
        $shareService = new \App\Services\InsuranceShareService();
        $shareSummary = $shareService->getSummary($share->id);

        return view('insurance.share-details', [
            'share' => $share,
            'family' => $family,
            'shareSummary' => $shareSummary
        ]);
    }

    /**
     * پاک کردن کش گزارش مالی - بهبود یافته
     */
    public function clearCache()
    {
        $keysBeforeFlush = $this->cacheHelper->getAllKeys();
        $flushResult = $this->cacheHelper->flush();
        
        \Illuminate\Support\Facades\Log::info('🗑️ کش گزارش مالی پاک شد', [
            'keys_cleared' => count($keysBeforeFlush),
            'keys_list' => $keysBeforeFlush,
            'flush_successful' => $flushResult,
            'user_id' => auth()->id()
        ]);
        
        if ($flushResult) {
            return back()->with('success', 'کش گزارش مالی و تمام کلیدهای مرتبط پاک شد. (تعداد: ' . count($keysBeforeFlush) . ')');
        } else {
            return back()->with('error', 'خطا در پاک کردن کش. لطفاً دوباره تلاش کنید.');
        }
    }
    
    /**
     * تخمین تعداد تراکنش‌ها برای validation
     */
    private function estimateTransactionCount(array $filters): int
    {
        $query = \App\Models\FundingTransaction::query();
        
        if (isset($filters['from_date'])) {
            $query->where('created_at', '>=', $filters['from_date']);
        }
        if (isset($filters['to_date'])) {
            $query->where('created_at', '<=', $filters['to_date']);
        }
        
        // تخمین سریع با count
        return $query->count() * 7; // ضرب در 7 چون 7 نوع تراکنش داریم
    }
    
    /**
     * دریافت آمار کش برای monitoring
     * فقط برای admin قابل دسترسی
     */
    public function getCacheStats()
    {
        // بررسی دسترسی admin
        if (!auth()->user() || !auth()->user()->can('view_cache_stats')) {
            abort(403, 'شما دسترسی لازم برای مشاهده آمار کش را ندارید.');
        }
        
        $stats = $this->cacheHelper->getStats();
        
        return response()->json([
            'status' => 'success',
            'data' => $stats,
            'generated_at' => now()->toISOString()
        ]);
    }
}
