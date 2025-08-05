<?php

namespace App\Http\Controllers\Charity;

use App\Http\Controllers\Controller;
use App\Imports\FamiliesImport;
use App\Exports\FamiliesTemplateExport;
use App\Jobs\ProcessFamiliesImport;
use App\Models\District;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;

class ImportController extends Controller
{
    /**
     * نمایش فرم آپلود فایل اکسل
     */
    public function index()
    {
        Gate::authorize('create family');

        $districts = District::active()->get();

        return view('charity.import.index', compact('districts'));
    }

    /**
     * آپلود و پردازش فایل اکسل خانواده‌ها
     */
    public function import(Request $request)
    {
        Gate::authorize('create family');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'district_id' => 'required|exists:districts,id',
        ], [
            'file.required' => 'انتخاب فایل الزامی است.',
            'file.file' => 'فایل انتخاب شده معتبر نیست.',
            'file.mimes' => 'فرمت فایل باید xlsx, xls یا csv باشد.',
            'file.max' => 'حجم فایل نباید بیشتر از 10 مگابایت باشد.',
            'district_id.required' => 'انتخاب منطقه الزامی است.',
            'district_id.exists' => 'منطقه انتخاب شده معتبر نیست.',
        ]);

        try {
            $file = $request->file('file');
            $originalFileName = $file->getClientOriginalName();

            // ذخیره فایل در storage موقت
            // مطمئن شدن از وجود پوشه uploads
            Storage::disk('public')->makeDirectory('uploads');

            $filePath = $file->store('uploads', 'public');

            // تشخیص اندازه فایل و تصمیم‌گیری
            $fileSize = $file->getSize();
            $isLargeFile = $fileSize > (2 * 1024 * 1024); // بیشتر از 2MB = فایل بزرگ

            if ($isLargeFile) {
                // پردازش در پس‌زمینه برای فایل‌های بزرگ
                $job = new ProcessFamiliesImport(
                    $request->user(),
                    $request->input('district_id'),
                    $filePath,
                    $originalFileName
                );

                dispatch($job);

                $message = "🚀 فایل شما در صف پردازش قرار گرفت!";
                $details = "فایل‌های بزرگ (بیش از 2 مگابایت) در پس‌زمینه پردازش می‌شوند. پس از اتمام پردازش، نتیجه از طریق اعلان به شما نمایش داده خواهد شد. لطفاً چند دقیقه صبر کنید.";

                return redirect()->route('charity.dashboard')
                    ->with('info', $message)
                    ->with('details', $details)
                    ->with('job_id', $job->getJobId());

            } else {
                // پردازش مستقیم برای فایل‌های کوچک
                $import = new FamiliesImport(
                    $request->user(),
                    $request->input('district_id')
                );

                ExcelFacade::import($import, Storage::disk('public')->path($filePath));

                $results = $import->getResults();

                // حذف فایل موقت
                Storage::disk('public')->delete($filePath);

                // تولید پیام موفقیت با جزئیات
                $message = $this->generateSuccessMessage($results, $originalFileName);

                return redirect()->route('charity.dashboard')
                    ->with('success', $message)
                    ->with('results', $results);
            }

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            // حذف فایل موقت در صورت خطا
            if (isset($filePath) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            $failures = $e->failures();
            $errors = $this->formatValidationErrors($failures);

            return redirect()->route('charity.dashboard')
                ->with('error', '❌ خطا در اعتبارسنجی فایل اکسل - لطفاً فایل را مطابق نمونه تصحیح کنید')
                ->with('results', [
                    'families_created' => 0,
                    'members_added' => 0,
                    'failed' => count($failures),
                    'errors' => $errors['limited'],
                    'total_errors' => count($failures),
                    'showing_count' => $errors['showing_count']
                ]);

        } catch (\Exception $e) {
            // حذف فایل موقت در صورت خطا
            if (isset($filePath) && Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }

            $errorMessage = $this->translateDatabaseError($e->getMessage(), $originalFileName);

            return redirect()->route('charity.dashboard')
                ->with('error', $errorMessage);
        }
    }

    /**
     * تولید پیام موفقیت با جزئیات
     */
    private function generateSuccessMessage(array $results, string $fileName): string
    {
        $message = "✅ فایل {$fileName} با موفقیت پردازش شد!";

        if ($results['families_created'] > 0) {
            $message .= "\n🏠 {$results['families_created']} خانواده جدید ثبت شد";
        }

        if ($results['members_added'] > 0) {
            $message .= "\n👥 {$results['members_added']} عضو جدید اضافه شد";
        }

        if ($results['failed'] > 0) {
            $message .= "\n⚠️ {$results['failed']} ردیف دارای مشکل بود و ثبت نشد";
        }

        if ($results['families_created'] == 0 && $results['members_added'] == 0) {
            $message = "❌ هیچ اطلاعات جدیدی از فایل {$fileName} ثبت نشد. لطفاً فایل را بررسی کنید.";
        }

        return $message;
    }

    /**
     * فرمت کردن خطاهای اعتبارسنجی (محدود به 5 عدد)
     */
    private function formatValidationErrors(array $failures): array
    {
        $errors = [];
        $totalCount = count($failures);
        $showingCount = min($totalCount, 5);

        for ($i = 0; $i < $showingCount; $i++) {
            $failure = $failures[$i];
            $errors[] = "ردیف {$failure->row()}: " . implode(', ', $failure->errors());
        }

        return [
            'limited' => $errors,
            'total_count' => $totalCount,
            'showing_count' => $showingCount
        ];
    }

    /**
     * ترجمه خطاهای پایگاه داده و عمومی به زبان قابل فهم
     */
    private function translateDatabaseError(string $errorMessage, string $fileName = ''): string
    {
        // خطای کد ملی تکراری
        if (str_contains($errorMessage, 'Duplicate entry') && str_contains($errorMessage, 'members_national_code_unique')) {
            preg_match('/Duplicate entry \'([^\']+)\'/', $errorMessage, $matches);
            $duplicateNationalCode = $matches[1] ?? 'نامشخص';

            return "⚠️ خطای اطلاعات تکراری در فایل {$fileName}: شخصی با کد ملی {$duplicateNationalCode} قبلاً در سیستم ثبت شده است. لطفاً اطلاعات تکراری را از فایل حذف کرده و مجدداً آپلود کنید.";
        }

        // خطای کد خانواده تکراری
        if (str_contains($errorMessage, 'Duplicate entry') && str_contains($errorMessage, 'families_family_code_unique')) {
            return "⚠️ خطای خانواده تکراری در فایل {$fileName}: این خانواده قبلاً در سیستم ثبت شده است. لطفاً خانواده‌های تکراری را از فایل حذف کنید.";
        }

        // خطای محدودیت کلید خارجی
        if (str_contains($errorMessage, 'foreign key constraint')) {
            if (str_contains($errorMessage, 'province_id')) {
                return "❌ خطا در اطلاعات استان: استان وارد شده در فایل {$fileName} معتبر نیست. لطفاً اطلاعات استان را بررسی کنید.";
            }
            if (str_contains($errorMessage, 'city_id')) {
                return "❌ خطا در اطلاعات شهر: شهر وارد شده در فایل {$fileName} معتبر نیست. لطفاً اطلاعات شهر را بررسی کنید.";
            }
            if (str_contains($errorMessage, 'district_id')) {
                return "❌ خطا در اطلاعات منطقه: منطقه وارد شده در فایل {$fileName} معتبر نیست. لطفاً اطلاعات منطقه را بررسی کنید.";
            }
            return "❌ خطا در ارتباط اطلاعات: یکی از فیلدهای وارد شده در فایل {$fileName} معتبر نیست.";
        }

        // خطاهای رایج فایل
        if (str_contains($errorMessage, 'file not found') || str_contains($errorMessage, 'فایل یافت نشد')) {
            return '📁 فایل انتخاب شده یافت نشد. لطفاً مجدداً فایل را انتخاب کنید.';
        }

        // خطای ایجاد پوشه (مخصوص لیارا)
        if (str_contains($errorMessage, 'Unable to create a directory') || str_contains($errorMessage, 'create directory')) {
            return '📁 مشکل در ذخیره‌سازی فایل: سیستم نتوانست پوشه موقت ایجاد کند. لطفاً مجدداً تلاش کنید.';
        }

        if (str_contains($errorMessage, 'permission denied') || str_contains($errorMessage, 'دسترسی مجاز نیست')) {
            return '🔐 دسترسی به فایل امکان‌پذیر نیست. لطفاً از فرمت صحیح اکسل استفاده کنید.';
        }

        if (str_contains($errorMessage, 'memory') || str_contains($errorMessage, 'حافظه')) {
            return '💾 فایل شما خیلی بزرگ است. لطفاً فایل کوچک‌تری آپلود کنید یا اطلاعات را در چند فایل تقسیم کنید.';
        }

        if (str_contains($errorMessage, 'timeout')) {
            return '⏱️ پردازش فایل خیلی طول کشید. لطفاً فایل کوچک‌تری آپلود کنید.';
        }

        // خطای فیلد خالی اجباری
        if (str_contains($errorMessage, 'cannot be null') || str_contains($errorMessage, 'not null')) {
            if (str_contains($errorMessage, 'first_name')) {
                return "❌ نام ضروری است: نام اعضای خانواده در فایل {$fileName} نباید خالی باشد.";
            }
            if (str_contains($errorMessage, 'national_code')) {
                return "❌ کد ملی ضروری است: کد ملی اعضای خانواده در فایل {$fileName} نباید خالی باشد.";
            }
            return "❌ فیلد اجباری خالی است: یکی از فیلدهای ضروری در فایل {$fileName} خالی باقی مانده.";
        }

        // خطای طول زیاد فیلد
        if (str_contains($errorMessage, 'Data too long for column')) {
            if (str_contains($errorMessage, 'national_code')) {
                return "❌ کد ملی طولانی: کد ملی در فایل {$fileName} نباید بیشتر از 10 رقم باشد.";
            }
            if (str_contains($errorMessage, 'phone')) {
                return "❌ شماره تلفن طولانی: شماره تلفن در فایل {$fileName} نباید بیشتر از 15 رقم باشد.";
            }
            if (str_contains($errorMessage, 'address')) {
                return "❌ آدرس طولانی: آدرس در فایل {$fileName} نباید بیشتر از 500 کاراکتر باشد.";
            }
            return "❌ داده طولانی: یکی از فیلدها در فایل {$fileName} بیش از حد مجاز طولانی است.";
        }

        // خطای عمومی connection
        if (str_contains($errorMessage, 'connection') || str_contains($errorMessage, 'timeout')) {
            return "🔌 مشکل ارتباط با پایگاه داده: لطفاً مجدداً تلاش کنید.";
        }

        // خطای table موجود نبودن
        if (str_contains($errorMessage, 'Base table or view not found') || str_contains($errorMessage, "doesn't exist")) {
            if (str_contains($errorMessage, 'family_members')) {
                return "❌ خطای پیکربندی: Table اعضای خانواده یافت نشد. لطفاً پیکربندی سیستم را بررسی کنید یا با پشتیبانی تماس بگیرید.";
            }
            if (str_contains($errorMessage, 'families')) {
                return "❌ خطای پیکربندی: Table خانواده‌ها یافت نشد. لطفاً پیکربندی سیستم را بررسی کنید یا با پشتیبانی تماس بگیرید.";
            }
            return "❌ خطای پیکربندی پایگاه داده: یکی از table های ضروری یافت نشد. لطفاً با ادمین سیستم تماس بگیرید.";
        }

        // خطای پیش‌فرض - خلاصه شده
        return "❌ خطا در پردازش فایل {$fileName}: " . (strlen($errorMessage) > 100 ?
            substr($errorMessage, 0, 100) . '...' :
            $errorMessage);
    }

    /**
     * دانلود فایل نمونه خانواده‌ها
     */
    public function downloadFamiliesTemplate()
    {
        Gate::authorize('create family');

        return ExcelFacade::download(
            new FamiliesTemplateExport(),
            'families_template.xlsx'
        );
    }

    /**
     * دانلود فایل نمونه (مسیر قدیمی برای سازگاری)
     */
    public function downloadTemplate()
    {
        return $this->downloadFamiliesTemplate();
    }

    /**
     * بررسی وضعیت job import
     */
    public function checkJobStatus(Request $request)
    {
        Gate::authorize('create family');

        $jobId = $request->query('job_id');

        if (!$jobId) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job ID ارائه نشده است.'
            ], 400);
        }

        $jobData = \Illuminate\Support\Facades\Cache::get("import_job_{$jobId}");

        if (!$jobData) {
            return response()->json([
                'status' => 'error',
                'message' => 'Job مورد نظر یافت نشد یا منقضی شده است.'
            ], 404);
        }

        // بررسی دسترسی کاربر
        if ($jobData['user_id'] !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'شما مجوز مشاهده این job را ندارید.'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $jobData
        ]);
    }
}
