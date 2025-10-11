<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Services\FinancialReportService;
use App\Exports\FinancialReportExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Job برای پردازش export گزارش مالی در background
 * 
 * این Job از الگوی ProcessInsuranceExcelImport استفاده می‌کند
 * و progress tracking با Cache انجام می‌دهد
 */
class ProcessFinancialReportExport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected array $filters;
    protected string $format;
    protected string $jobId;

    public int $timeout = 1800; // 30 دقیقه
    public int $tries = 2; // 2 بار تلاش

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, array $filters, string $format)
    {
        $this->user = $user;
        $this->filters = $filters;
        $this->format = $format;
        $this->jobId = uniqid('financial_export_');
        
        // ثبت وضعیت اولیه در Cache
        $this->updateStatus('queued', 0);
    }

    /**
     * Execute the job.
     */
    public function handle(FinancialReportService $financialReportService): void
    {
        try {
            Log::info('📤 شروع پردازش export گزارش مالی', [
                'job_id' => $this->jobId,
                'user_id' => $this->user->id,
                'filters' => $this->filters,
                'format' => $this->format
            ]);

            // مرحله 1: Validation تعداد رکورد
            $allTransactions = $financialReportService->getAllTransactions($this->filters);
            $count = $allTransactions->count();

            if ($count > 10000) {
                $message = "تعداد تراکنش‌ها ({$count}) بیش از حد مجاز (10000) است";
                $this->updateStatus('failed', 0, null, $message);
                throw new \Exception($message);
            }

            Log::info('✅ Validation تعداد رکورد موفق', [
                'job_id' => $this->jobId,
                'records_count' => $count
            ]);

            $this->updateStatus('processing', 10);

            // مرحله 2: آماده‌سازی Export
            $this->updateStatus('processing', 30);
            
            $export = new FinancialReportExport($this->filters);
            
            // تولید نام فایل منحصر به فرد
            $filename = "financial_report_{$this->user->id}_{$this->jobId}.{$this->format}";
            $filePath = "exports/financial/{$filename}";

            Log::info('📁 آماده‌سازی فایل export', [
                'job_id' => $this->jobId,
                'filename' => $filename,
                'file_path' => $filePath
            ]);

            $this->updateStatus('processing', 60);

            // مرحله 3: ذخیره فایل
            // اطمینان از وجود دایرکتوری
            if (!Storage::disk('public')->exists('exports/financial')) {
                Storage::disk('public')->makeDirectory('exports/financial');
            }

            $writerType = $this->format === 'csv' ? 
                \Maatwebsite\Excel\Excel::CSV : 
                \Maatwebsite\Excel\Excel::XLSX;

            Excel::store($export, $filePath, 'public', $writerType);

            $this->updateStatus('processing', 90);

            // مرحله 4: بررسی و تکمیل
            if (!Storage::disk('public')->exists($filePath)) {
                throw new \Exception('فایل export با موفقیت ایجاد نشد');
            }

            $fileSize = Storage::disk('public')->size($filePath);

            Log::info('🎉 Export گزارش مالی با موفقیت تکمیل شد', [
                'job_id' => $this->jobId,
                'user_id' => $this->user->id,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'records_count' => $count
            ]);

            $this->updateStatus('completed', 100, [
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'records_count' => $count,
                'filename' => $filename
            ]);

        } catch (Throwable $exception) {
            Log::error('❌ خطا در export گزارش مالی', [
                'job_id' => $this->jobId,
                'user_id' => $this->user->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            $this->updateStatus('failed', 0, null, $exception->getMessage());
            throw $exception;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('💥 Job export گزارش مالی ناموفق', [
            'job_id' => $this->jobId,
            'user_id' => $this->user->id,
            'error' => $exception->getMessage()
        ]);

        $this->updateStatus('failed', 0, null, $exception->getMessage());

        // حذف فایل موقت اگر وجود دارد
        $filename = "financial_report_{$this->user->id}_{$this->jobId}.{$this->format}";
        $filePath = "exports/financial/{$filename}";
        
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
            Log::info('🗑️ فایل موقت حذف شد', ['file_path' => $filePath]);
        }
    }

    /**
     * به‌روزرسانی وضعیت Job در Cache
     */
    private function updateStatus(string $status, int $progress, ?array $results = null, ?string $error = null): void
    {
        $data = [
            'job_id' => $this->jobId,
            'user_id' => $this->user->id,
            'status' => $status,
            'progress' => $progress,
            'filters' => $this->filters,
            'format' => $this->format,
            'results' => $results,
            'error' => $error,
            'updated_at' => now()->toISOString()
        ];

        // اضافه کردن timestamps خاص
        if ($status === 'processing' && $progress === 10) {
            $data['started_at'] = now()->toISOString();
        }

        if (in_array($status, ['completed', 'failed'])) {
            $data['finished_at'] = now()->toISOString();
        }

        $cacheKey = "financial_export_job_{$this->jobId}";
        Cache::put($cacheKey, $data, 3600); // 1 ساعت TTL

        Log::debug('📊 وضعیت Job به‌روزرسانی شد', [
            'job_id' => $this->jobId,
            'status' => $status,
            'progress' => $progress
        ]);
    }

    /**
     * دریافت شناسه Job
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }
}