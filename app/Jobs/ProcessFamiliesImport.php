<?php

namespace App\Jobs;

use App\Imports\FamiliesImport;
use App\Models\User;
use App\Services\Notification\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProcessFamiliesImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected int $districtId;
    protected string $filePath;
    protected string $originalFileName;
    protected string $jobId;

    /**
     * مدت زمان timeout برای job (30 دقیقه)
     */
    public $timeout = 1800;

    /**
     * تعداد retry در صورت شکست
     */
    public $tries = 3;

    /**
     * ایجاد یک نمونه جدید از job
     */
    public function __construct(User $user, int $districtId, string $filePath, string $originalFileName)
    {
        $this->user = $user;
        $this->districtId = $districtId;
        $this->filePath = $filePath;
        $this->originalFileName = $originalFileName;
        $this->jobId = uniqid('import_');
        
        // ذخیره اطلاعات job در cache برای tracking
        Cache::put("import_job_{$this->jobId}", [
            'user_id' => $user->id,
            'status' => 'queued',
            'started_at' => null,
            'finished_at' => null,
            'progress' => 0,
            'file_name' => $originalFileName,
            'results' => null,
        ], 3600); // 1 ساعت
    }

    /**
     * اجرای job
     */
    public function handle(): void
    {
        $this->updateStatus('processing', 0);
        
        try {
                'user_id' => $this->user->id,
                'file' => $this->originalFileName,
                'job_id' => $this->jobId
            ]);

            // بررسی وجود فایل
            if (!Storage::disk('public')->exists($this->filePath)) {
                throw new \Exception("فایل آپلود شده یافت نشد: {$this->filePath}");
            }

            $this->updateStatus('processing', 10);

            // پردازش فایل
            $import = new FamiliesImport($this->user, $this->districtId);
            
            $this->updateStatus('processing', 30);
            
            Excel::import($import, Storage::disk('public')->path($this->filePath));
            
            $this->updateStatus('processing', 80);
            
            $results = $import->getResults();
            
            $this->updateStatus('completed', 100, $results);
            
            // ارسال اعلان موفقیت
            if ($results['families_created'] > 0 || $results['members_added'] > 0) {
                $this->sendSuccessNotification($results);
            } else {
                // اگر هیچ چیز ایجاد نشده، اعلان خطا بفرست
                $this->sendErrorNotification("هیچ خانواده یا عضو جدیدی از فایل ایجاد نشد. لطفاً فایل را بررسی کنید.");
            }
            
            // حذف فایل موقت
            Storage::disk('public')->delete($this->filePath);
            
                'user_id' => $this->user->id,
                'job_id' => $this->jobId,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            $this->updateStatus('failed', 0, null, $e->getMessage());
            
            // ارسال اعلان خطا
            $this->sendErrorNotification($e->getMessage());
            
                'user_id' => $this->user->id,
                'job_id' => $this->jobId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * مدیریت شکست job
     */
    public function failed(\Throwable $exception): void
    {
        $this->updateStatus('failed', 0, null, $exception->getMessage());
        
        // حذف فایل موقت در صورت شکست
        if (Storage::disk('public')->exists($this->filePath)) {
            Storage::disk('public')->delete($this->filePath);
        }
        
        $this->sendErrorNotification($exception->getMessage());
        
            'user_id' => $this->user->id,
            'job_id' => $this->jobId,
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * بروزرسانی وضعیت job
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
            'updated_at' => now(),
        ];

        if ($status === 'processing' && $progress === 0) {
            $data['started_at'] = now();
        }

        if (in_array($status, ['completed', 'failed'])) {
            $data['finished_at'] = now();
        }

        Cache::put("import_job_{$this->jobId}", $data, 3600);
    }

    /**
     * ارسال اعلان موفقیت
     */
    private function sendSuccessNotification(array $results): void
    {
        try {
            // تولید پیام موفقیت بهتر
            $message = $this->generateSuccessMessage($results);

            // ارسال پیام تلگرام (اگر سرویس موجود باشد)
            // if (class_exists(TelegramChannel::class)) {
            //     $telegram = app(TelegramChannel::class);
            //     $telegram->sendMessage($this->user->telegram_chat_id ?? '', $message);
            // }

        } catch (\Exception $e) {
                'error' => $e->getMessage(),
                'user_id' => $this->user->id
            ]);
        }
    }

    /**
     * ارسال اعلان خطا
     */
    private function sendErrorNotification(string $error): void
    {
        try {
            // تولید پیام خطا بهتر
            $message = $this->generateErrorMessage($error);

            // // ارسال پیام تلگرام (اگر سرویس موجود باشد)
            // if (class_exists(TelegramChannel::class)) {
            //     $telegram = app(TelegramChannel::class);
            //     $telegram->sendMessage($this->user->telegram_chat_id ?? '', $message);
            // }

        } catch (\Exception $e) {
                'error' => $e->getMessage(),
                'user_id' => $this->user->id
            ]);
        }
    }

    /**
     * تولید پیام موفقیت با جزئیات
     */
    private function generateSuccessMessage(array $results): string
    {
        $message = "✅ **آپلود فایل خانواده‌ها با موفقیت انجام شد**\n\n";
        $message .= "📋 **نام فایل:** {$this->originalFileName}\n";
        
        // آمار کلی
        if ($results['families_created'] > 0) {
            $message .= "🏠 **خانواده‌های جدید:** {$results['families_created']} خانواده\n";
        }
        
        if ($results['members_added'] > 0) {
            $message .= "👥 **اعضای جدید:** {$results['members_added']} نفر\n";
        }
        
        // نمایش خطاها در صورت وجود
        if ($results['failed'] > 0) {
            $message .= "⚠️ **ردیف‌های ناموفق:** {$results['failed']} مورد\n";
            $message .= "💡 **توجه:** ردیف‌های ناموفق به دلیل اطلاعات ناقص یا نامعتبر ثبت نشدند.\n";
        }
        
        // پیام نهایی
        if ($results['families_created'] > 0 || $results['members_added'] > 0) {
            $message .= "\n🎉 **پردازش با موفقیت کامل شد!**";
            $message .= "\n\n📊 **وضعیت:** آماده برای بررسی و تایید";
        } else {
            $message .= "\n❌ **هیچ اطلاعات جدیدی ثبت نشد**";
            $message .= "\n💡 **راهنمایی:** لطفاً فایل را بررسی کرده و مطابق نمونه آماده کنید.";
        }
        
        return $message;
    }

    /**
     * تولید پیام خطا بهتر
     */
    private function generateErrorMessage(string $error): string
    {
        $message = "❌ **خطا در پردازش فایل خانواده‌ها**\n\n";
        $message .= "📋 **نام فایل:** {$this->originalFileName}\n";
        
        // استفاده از همان متد ترجمه خطا که در Import استفاده می‌شود
        $translatedError = $this->translateDatabaseError($error);
        
        // تشخیص نوع خطا و ارائه راهنمایی مناسب
        if (str_contains($error, 'Duplicate entry')) {
            $message .= "🚫 **مشکل:** اطلاعات تکراری در فایل\n";
            $message .= "📝 **جزئیات:** {$translatedError}\n";
            $message .= "💡 **راه حل:** لطفاً قبل از آپلود، اطمینان حاصل کنید که اطلاعات قبلاً در سیستم ثبت نشده باشد\n";
        } elseif (str_contains($error, 'memory') || str_contains($error, 'حافظه')) {
            $message .= "🚫 **علت خطا:** حجم فایل خیلی زیاد است\n";
            $message .= "💡 **راه حل:** فایل را به قسمت‌های کوچک‌تر تقسیم کنید\n";
        } elseif (str_contains($error, 'timeout') || str_contains($error, 'زمان')) {
            $message .= "🚫 **علت خطا:** پردازش فایل خیلی طول کشید\n";
            $message .= "💡 **راه حل:** فایل کوچک‌تری آپلود کنید\n";
        } elseif (str_contains($error, 'validation') || str_contains($error, 'اعتبارسنجی')) {
            $message .= "🚫 **علت خطا:** فرمت یا محتوای فایل نامعتبر است\n";
            $message .= "💡 **راه حل:** فایل را مطابق نمونه ارائه شده آماده کنید\n";
        } elseif (str_contains($error, 'file') || str_contains($error, 'فایل')) {
            $message .= "🚫 **علت خطا:** مشکل در دسترسی به فایل\n";
            $message .= "💡 **راه حل:** فایل را مجدداً آپلود کنید\n";
        } else {
            $message .= "🚫 **علت خطا:** {$translatedError}\n";
            $message .= "💡 **راه حل:** لطفاً مجدداً تلاش کنید\n";
        }
        
        $message .= "\n🔄 **اقدام بعدی:** در صورت تکرار مشکل، با پشتیبانی تماس بگیرید.";
        
        return $message;
    }

    /**
     * ترجمه خطاهای پایگاه داده به زبان قابل فهم (کپی از FamiliesImport)
     */
    private function translateDatabaseError(string $errorMessage): string
    {
        // خطای کد ملی تکراری
        if (str_contains($errorMessage, 'Duplicate entry') && str_contains($errorMessage, 'members_national_code_unique')) {
            preg_match('/Duplicate entry \'([^\']+)\'/', $errorMessage, $matches);
            $duplicateNationalCode = $matches[1] ?? 'نامشخص';
            
            return "⚠️ کد ملی تکراری: شخصی با کد ملی {$duplicateNationalCode} قبلاً در سیستم ثبت شده است";
        }
        
        // خطای کد خانواده تکراری
        if (str_contains($errorMessage, 'Duplicate entry') && str_contains($errorMessage, 'families_family_code_unique')) {
            return "⚠️ کد خانواده تکراری: این خانواده قبلاً در سیستم ثبت شده است";
        }
        
        // خطای محدودیت کلید خارجی
        if (str_contains($errorMessage, 'foreign key constraint')) {
            return "❌ خطا در ارتباط اطلاعات: یکی از فیلدهای وارد شده معتبر نیست";
        }
        
        // خطای فیلد خالی اجباری
        if (str_contains($errorMessage, 'cannot be null') || str_contains($errorMessage, 'not null')) {
            return "❌ فیلد اجباری خالی است: یکی از فیلدهای ضروری خالی باقی مانده";
        }
        
        // خطای طول زیاد فیلد
        if (str_contains($errorMessage, 'Data too long for column')) {
            return "❌ داده طولانی: یکی از فیلدها بیش از حد مجاز طولانی است";
        }
        
        // خطای table موجود نبودن
        if (str_contains($errorMessage, 'Base table or view not found') || str_contains($errorMessage, "doesn't exist")) {
            return "❌ خطای پیکربندی پایگاه داده: لطفاً با پشتیبانی تماس بگیرید";
        }
        
        // خطاهای عمومی دیگر - خلاصه شده
        return strlen($errorMessage) > 150 ? 
            substr($errorMessage, 0, 150) . '...' : 
            $errorMessage;
    }

    /**
     * دریافت ID job
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }
}
