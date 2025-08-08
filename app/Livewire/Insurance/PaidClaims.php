<?php
namespace App\Livewire\Insurance;

use Livewire\Component;
use App\Models\InsuranceAllocation;
use App\Models\Family;
use App\Models\FundingTransaction;
use Illuminate\Validation\Rule;
use Illuminate\Support\Collection;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use App\Services\ClaimsImportService;

class PaidClaims extends Component
{
    use WithPagination, WithFileUploads;

    public bool $addMode = false;
    public ?int $editId = null;
    public ?float $amount = null;
    public ?int $funding_transaction_id = null;
    public Collection $families;
    public Collection $transactions;
    public ?string $issue_date = null;
    public ?string $paid_at = null;
    public ?string $description = null;
    public ?int $family_id = null;
    public ?object $selectedFamily = null;
    public ?object $selectedTransaction = null;
    public int $perPage = 10;
    public int $page = 1;
    protected $queryString = ['perPage', 'page'];
    
    // Excel upload properties
    public bool $showExcelUploadModal = false;
    public ?UploadedFile $excelFile = null;
    public bool $isUploading = false;
    public ?string $uploadProgress = null;

    protected array $rules = [
        'family_id' => 'nullable|exists:families,id',
        'amount' => 'required|numeric|min:1',
        'funding_transaction_id' => 'nullable|exists:funding_transactions,id',
        'issue_date' => 'nullable|string|max:20',
        'paid_at' => 'nullable|string|max:20',
        'description' => 'nullable|string|max:255',
        'excelFile' => 'nullable|file|mimes:xlsx,xls|max:2048',
    ];

    protected array $messages = [
        'family_id.exists' => 'خانواده انتخابی معتبر نیست.',
        'amount.required' => 'مبلغ الزامی است.',
        'amount.numeric' => 'مبلغ باید عددی باشد.',
        'amount.min' => 'مبلغ باید بیشتر از صفر باشد.',
        'funding_transaction_id.exists' => 'تراکنش انتخابی معتبر نیست.',
        'excelFile.required' => 'لطفا فایل اکسل را انتخاب کنید.',
        'excelFile.file' => 'فایل انتخابی معتبر نیست.',
        'excelFile.mimes' => 'فایل باید از نوع Excel (.xlsx, .xls) باشد.',
        'excelFile.max' => 'حجم فایل نباید بیشتر از 2 مگابایت باشد.',
    ];

    public function mount()
    {
        $this->families = $this->getCachedFamilies();
        $this->transactions = $this->getCachedTransactions();
        $this->selectedFamily = null;
        $this->selectedTransaction = null;
    }

    protected function getCachedFamilies()
    {
        $cacheKey = 'insurance.families.list';
        $ttl = now()->addHours(6); // کش به مدت 6 ساعت معتبر است

        return Cache::remember($cacheKey, $ttl, function () {
            return Family::select('id', 'family_code')
                ->with(['head' => function($query) {
                    $query->select('id', 'family_id', 'first_name', 'last_name', 'mobile');
                }])
                ->get();
        });
    }

    protected function getCachedTransactions()
    {
        $cacheKey = 'insurance.funding_transactions.list';
        $ttl = now()->addHours(6); // کش به مدت 6 ساعت معتبر است

        return Cache::remember($cacheKey, $ttl, function () {
            return FundingTransaction::select('id', 'reference_no', 'amount', 'description')
                ->where('status', 'completed')
                ->orWhereNull('status') // برای تراکنش‌های قدیمی که status ندارند
                ->get();
        });
    }

    public function addClaim()
    {
        $this->validate();

        DB::transaction(function () {
            $claim = InsuranceAllocation::create([
                'family_id' => $this->family_id,
                'amount' => $this->amount,
                'funding_transaction_id' => $this->funding_transaction_id,
                'issue_date' => $this->issue_date,
                'paid_at' => $this->paid_at,
                'description' => $this->description,
            ]);

            // پاک کردن کش‌های مربوطه
            $this->clearRelatedCaches();
        });

        $this->resetForm();
        $this->resetPage();
        $this->addMode = false;
        session()->flash('success', 'خسارت با موفقیت ثبت شد.');
    }

    public function deleteClaim($id)
    {
        DB::transaction(function () use ($id) {
            $claim = InsuranceAllocation::findOrFail($id);
            $claim->delete();

            // پاک کردن کش‌های مربوطه
            $this->clearRelatedCaches();
        });

        $this->resetPage();
        session()->flash('success', 'خسارت حذف شد.');
    }

    public function showAddForm()
    {
        $this->addMode = true;
        $this->resetForm();
    }

    public function cancelAdd()
    {
        $this->addMode = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->family_id = null;
        $this->selectedFamily = null;
        $this->amount = null;
        $this->funding_transaction_id = null;
        $this->selectedTransaction = null;
        $this->issue_date = null;
        $this->paid_at = null;
        $this->description = null;
    }

    public function updatedFamilyId($value)
    {
        $found = $this->families->firstWhere('id', $value);
        $this->selectedFamily = $found ? (object) $found->toArray() : null;
    }

    public function updatedFundingTransactionId($value)
    {
        $found = $this->transactions->firstWhere('id', $value);
        $this->selectedTransaction = $found ? (object) $found->toArray() : null;
    }

    public function editClaim($id)
    {
        $claim = InsuranceAllocation::findOrFail($id);
        $this->editId = $claim->id;
        $this->family_id = $claim->family_id;
        $this->amount = $claim->amount;
        $this->funding_transaction_id = $claim->funding_transaction_id;
        $this->issue_date = $claim->issue_date;
        $this->paid_at = $claim->paid_at;
        $this->description = $claim->description;
        $this->addMode = false;
        $foundFamily = $this->families->firstWhere('id', $claim->family_id);
        $this->selectedFamily = $foundFamily ? (object) $foundFamily->toArray() : null;
        
        $foundTx = $this->transactions->firstWhere('id', $claim->funding_transaction_id);
        $this->selectedTransaction = $foundTx ? (object) $foundTx->toArray() : null;
    }

    public function updateClaim()
    {
        $this->validate();

        DB::transaction(function () {
            $claim = InsuranceAllocation::findOrFail($this->editId);
            $claim->update([
                'family_id' => $this->family_id,
                'amount' => $this->amount,
                'funding_transaction_id' => $this->funding_transaction_id,
                'issue_date' => $this->issue_date,
                'paid_at' => $this->paid_at,
                'description' => $this->description,
            ]);

            // پاک کردن کش‌های مربوطه
            $this->clearRelatedCaches();
        });

        $this->resetForm();
        $this->editId = null;
        $this->selectedFamily = null;
        $this->selectedTransaction = null;
        $this->resetPage();
        session()->flash('success', 'خسارت با موفقیت ویرایش شد.');
    }

    /**
     * پاک کردن کش‌های مربوطه
     */
    protected function clearRelatedCaches()
    {
        // پاک کردن کش‌های اصلی
        Cache::forget('insurance.families.list');
        Cache::forget('insurance.funding_transactions.list');
        
        // پاک کردن کش‌های تجمعی (summary) - بدون استفاده از tagging
        $summaryKeys = [
            'claims_overall_stats',
            'available_insurance_types'
        ];
        
        // پاک کردن کلیدهای دینامیک summary
        $years = range(date('Y') - 2, date('Y') + 1);
        $types = ['summary', 'monthly', 'top_families'];
        
        foreach ($years as $year) {
            foreach ($types as $type) {
                $summaryKeys[] = "claims_summary_{$type}_{$year}-01-01_{$year}-12-31_{$year}_";
                $summaryKeys[] = "claims_summary_{$type}_{$year}-01-01_{$year}-12-31_{$year}_all";
            }
        }
        
        foreach ($summaryKeys as $key) {
            Cache::forget($key);
        }

        // پاک کردن کش لیست خسارات (مهم‌ترین بخش)
        $currentCacheKey = 'insurance.claims.page.' . $this->page . '.perpage.' . $this->perPage;
        Cache::forget($currentCacheKey);
        
        // پاک کردن کش‌های صفحه‌بندی
        if (config('cache.default') === 'redis') {
            $this->clearRedisPaginatedCaches('insurance.claims.page.');
        } else {
            // برای درایورهای دیگر مثل file یا database
            $this->clearPaginatedCaches('insurance.claims.page.');
        }
    }

    /**
     * پاک کردن کش‌های صفحه‌بندی در ردیس
     */
    protected function clearRedisPaginatedCaches($prefix)
    {
        try {
            $redis = Redis::connection();
            $keys = $redis->keys('*' . $prefix . '*');

            if (!empty($keys)) {
                $redis->del($keys);
            }
        } catch (\Exception $e) {
            // لاگ خطا در صورت نیاز
            Log::error('خطا در پاک کردن کش‌های ردیس: ' . $e->getMessage());
        }
    }

    /**
     * پاک کردن کش‌های صفحه‌بندی برای درایورهای دیگر
     */
    protected function clearPaginatedCaches($prefix)
    {
        // برای درایورهای غیر از ردیس، کش را به صورت دستی پاک می‌کنیم
        $store = Cache::getStore();

        if (method_exists($store, 'getPrefix')) {
            $prefix = $store->getPrefix() . $prefix;
        }

        // پاک کردن کش‌های صفحه‌بندی
        for ($i = 1; $i <= 100; $i++) { // حداکثر 100 صفحه
            Cache::forget($prefix . $i . '.perpage.' . $this->perPage);
        }
    }

    public function cancelEdit()
    {
        $this->editId = null;
        $this->resetForm();
    }

    /**
     * نمایش مودال آپلود فایل اکسل
     */
    public function showExcelUpload()
    {
        $this->showExcelUploadModal = true;
        $this->excelFile = null;
        $this->uploadProgress = null;
    }

    /**
     * بستن مودال آپلود فایل اکسل
     */
    public function hideExcelUpload()
    {
        $this->showExcelUploadModal = false;
        $this->excelFile = null;
        $this->uploadProgress = null;
        $this->isUploading = false;
    }

    /**
     * آپلود و پردازش فایل اکسل خسارات
     */
    public function importExcel()
    {
        $this->validate([
            'excelFile' => 'required|file|mimes:xlsx,xls|max:2048'
        ]);

        $this->isUploading = true;
        $this->uploadProgress = 'در حال آپلود فایل...';

        try {
            $originalFileName = $this->excelFile->getClientOriginalName();
            
            // ذخیره فایل به صورت موقت با نام یونیک
            $uniqueFileName = uniqid() . '_' . time() . '.' . $this->excelFile->getClientOriginalExtension();
            
            // ذخیره فایل با استفاده از Laravel's storage system
            $storedPath = $this->excelFile->storeAs('temp', $uniqueFileName, 'local');
            
            // بدست آوردن مسیر کامل فایل با استفاده از Laravel's Storage facade
            $fullFilePath = Storage::disk('local')->path($storedPath);
            
            // لاگ جزئیات مسیرها برای دیباگ
            Log::info('مسیرهای فایل آپلود شده', [
                'stored_path' => $storedPath,
                'full_file_path' => $fullFilePath,
                'file_exists' => file_exists($fullFilePath)
            ]);
            
            // بررسی وجود فایل
            if (!file_exists($fullFilePath)) {
                throw new \Exception('فایل پس از آپلود یافت نشد: ' . $fullFilePath);
            }
            
            Log::info('فایل اکسل با موفقیت ذخیره شد', [
                'original_name' => $originalFileName,
                'saved_path' => $fullFilePath,
                'file_size' => filesize($fullFilePath)
            ]);
            
            $this->uploadProgress = 'در حال پردازش داده‌ها...';
            
            // پردازش فایل اکسل
            $claimsImportService = new ClaimsImportService();
            $results = $claimsImportService->processClaimsExcel(
                $fullFilePath,
                $originalFileName
            );

            // حذف فایل موقت
            if (file_exists($fullFilePath)) {
                unlink($fullFilePath);
            }

            // پاک کردن کش‌های مربوطه
            $this->clearRelatedCaches();
            
            // نمایش نتایج
            $message = $this->formatImportResults($results);
            
            $this->hideExcelUpload();
            $this->resetPage();
            
            session()->flash('success', $message);
            
        } catch (\Exception $e) {
            // حذف فایل موقت در صورت خطا
            if (isset($fullFilePath) && file_exists($fullFilePath)) {
                try {
                    unlink($fullFilePath);
                } catch (\Exception $deleteException) {
                    Log::warning('نمی‌توان فایل موقت را حذف کرد', [
                        'file_path' => $fullFilePath,
                        'error' => $deleteException->getMessage()
                    ]);
                }
            }
            
            Log::error('خطا در آپلود فایل اکسل خسارات', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // در صورت خطا هم مودال را می‌بندیم
            $this->hideExcelUpload();
            
            session()->flash('error', 'خطا در پردازش فایل اکسل: ' . $e->getMessage());
        } finally {
            // اطمینان از بازنشانی وضعیت
            $this->isUploading = false;
            $this->uploadProgress = null;
        }
    }

    /**
     * فرمت کردن نتایج ایمپورت برای نمایش
     */
    private function formatImportResults(array $results): string
    {
        $parts = [];
        
        if ($results['created'] > 0) {
            $parts[] = "تعداد {$results['created']} خسارت جدید ثبت شد";
        }
        
        if ($results['updated'] > 0) {
            $parts[] = "تعداد {$results['updated']} خسارت به‌روزرسانی شد";
        }
        
        if ($results['skipped'] > 0) {
            $parts[] = "تعداد {$results['skipped']} ردیف نادیده گرفته شد";
        }
        
        if (!empty($results['errors'])) {
            $parts[] = "تعداد " . count($results['errors']) . " خطا شناسایی شد";
        }
        
        if (isset($results['total_claims_amount']) && $results['total_claims_amount'] > 0) {
            $parts[] = "مجموع مبلغ خسارات: " . number_format($results['total_claims_amount']) . " تومان";
        }
        
        return implode(' | ', $parts) ?: 'فایل اکسل پردازش شد';
    }

    public function getClaimsProperty()
    {
        $cacheKey = 'insurance.claims.page.' . $this->page . '.perpage.' . $this->perPage;
        $ttl = now()->addMinutes(30); // کش به مدت 30 دقیقه معتبر است

        return Cache::remember($cacheKey, $ttl, function () {
            return InsuranceAllocation::with([
                    'family' => function($query) {
                        $query->select('id', 'family_code')
                            ->with(['head' => function($q) {
                                $q->select('id', 'family_id', 'first_name', 'last_name', 'mobile');
                            }]);
                    },
                    'transaction' => function($query) {
                        $query->select('id', 'reference_no', 'amount', 'description');
                    }
                ])
                ->latest()
                ->paginate($this->perPage);
        });
    }

    public function render()
    {
        return view('livewire.insurance.paid-claims', [
            'addMode' => $this->addMode,
            'families' => $this->families,
            'transactions' => $this->transactions,
            'claims' => $this->getClaimsProperty(),
            'showExcelUploadModal' => $this->showExcelUploadModal,
            'isUploading' => $this->isUploading,
            'uploadProgress' => $this->uploadProgress,
            'excelFile' => $this->excelFile,
        ]);
    }
}
