<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\InsuranceShareService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessInsuranceExcelImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected string $filePath;
    protected string $originalFileName;
    protected string $jobId;

    public int $timeout = 1800; // 30 minutes
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $filePath, string $originalFileName)
    {
        $this->user = $user;
        $this->filePath = $filePath;
        $this->originalFileName = $originalFileName;
        $this->jobId = uniqid('insurance_import_');

        // Initialize cache entry
        $this->updateStatus('queued', 0);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->updateStatus('processing', 10);
            
            Log::info('Starting insurance Excel import processing', [
                'user_id' => $this->user->id,
                'file' => $this->originalFileName,
                'job_id' => $this->jobId,
                'job_context' => 'async'
            ]);

            // Check if file exists
            if (!Storage::disk('public')->exists($this->filePath)) {
                throw new \Exception("File not found: {$this->filePath}");
            }

            $this->updateStatus('processing', 30);

            // Get full path for the service
            $fullPath = Storage::disk('public')->path($this->filePath);
            
            // Call the service to process the Excel file
            $insuranceService = new InsuranceShareService();
            $results = $insuranceService->completeInsuranceFromExcel($fullPath);

            $this->updateStatus('processing', 80);

            // Add file name to results
            $results['file_name'] = $this->originalFileName;

            $this->updateStatus('completed', 100, $results);

            // Send success notification if records were created/updated
            if (!empty($results['created']) || !empty($results['updated'])) {
                $this->sendSuccessNotification($results);
            }

            // Delete the temporary file
            Storage::disk('public')->delete($this->filePath);

            Log::info('Insurance Excel import completed successfully', [
                'job_id' => $this->jobId,
                'results' => $results,
                'job_context' => 'async'
            ]);

        } catch (\Exception $e) {
            $this->updateStatus('failed', 0, null, $e->getMessage());
            $this->sendErrorNotification($e->getMessage());
            
            Log::error('Insurance Excel import failed', [
                'user_id' => $this->user->id,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'job_context' => 'async'
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->updateStatus('failed', 0, null, $exception->getMessage());

        // Delete temporary file if it exists
        if (Storage::disk('public')->exists($this->filePath)) {
            Storage::disk('public')->delete($this->filePath);
        }

        $this->sendErrorNotification($exception->getMessage());

        Log::error('Insurance Excel import job failed', [
            'user_id' => $this->user->id,
            'job_id' => $this->jobId,
            'error' => $exception->getMessage(),
            'job_context' => 'async'
        ]);
    }

    /**
     * Update job status in cache.
     */
    private function updateStatus(string $status, int $progress, ?array $results = null, ?string $error = null): void
    {
        $data = [
            'user_id' => $this->user->id,
            'status' => $status,
            'progress' => $progress,
            'file_name' => $this->originalFileName,
            'results' => $results,
            'error' => $error,
            'updated_at' => now()->toISOString(),
        ];

        // Set started_at when processing begins
        if ($status === 'processing' && $progress === 10) {
            $data['started_at'] = now()->toISOString();
        }

        // Set finished_at when completed or failed
        if (in_array($status, ['completed', 'failed'])) {
            $data['finished_at'] = now()->toISOString();
        }

        Cache::put("insurance_import_job_{$this->jobId}", $data, 3600); // 1 hour TTL
    }

    /**
     * Send success notification.
     */
    private function sendSuccessNotification(array $results): void
    {
        $created = $results['created'] ?? 0;
        $updated = $results['updated'] ?? 0;
        $skipped = $results['skipped'] ?? 0;

        $message = "بارگذاری فایل بیمه با موفقیت انجام شد.\n";
        $message .= "ایجاد شده: {$created}\n";
        $message .= "بروزرسانی شده: {$updated}\n";
        $message .= "رد شده: {$skipped}";

        // TODO: Integrate with Telegram or other notification channels
        // $this->sendTelegramNotification($message);

        Log::info('Success notification prepared for insurance import', [
            'job_id' => $this->jobId,
            'message' => $message,
            'job_context' => 'async'
        ]);
    }

    /**
     * Send error notification.
     */
    private function sendErrorNotification(string $error): void
    {
        $message = "خطا در بارگذاری فایل بیمه:\n{$error}";

        // TODO: Integrate with notification channels
        // $this->sendTelegramNotification($message);

        Log::info('Error notification prepared for insurance import', [
            'job_id' => $this->jobId,
            'error' => $error,
            'job_context' => 'async'
        ]);
    }

    /**
     * Get the job ID for tracking.
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }
}