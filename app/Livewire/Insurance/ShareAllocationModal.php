<?php

namespace App\Livewire\Insurance;

use Livewire\Component;
use App\Models\Family;
use App\Models\FundingSource;
use App\Services\InsuranceShareService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ShareAllocationModal extends Component
{
    public $showModal = false;
    public $familyIds = [];
    public $shares = [];
    public $fundingSources = [];
    public $totalPercentage = 0;
    public $errorMessage = '';
    public $successMessage = '';
    public $isProcessing = false;

    // در Livewire 3 از متد getListeners به جای $listeners استفاده می‌شود
    public function getListeners()
    {
        return [
            'openShareAllocationModal' => 'openModal',
            'refreshShareAllocation' => '$refresh'
        ];
    }

    protected $rules = [
        'shares' => 'required|array|min:1',
        'shares.*.funding_source_id' => 'required|exists:funding_sources,id',
        'shares.*.percentage' => 'required|numeric|min:0.01|max:100',
        'shares.*.description' => 'nullable|string|max:1000',
    ];

    protected $messages = [
        'shares.required' => 'حداقل یک منبع پرداخت وارد کنید.',
        'shares.min' => 'حداقل یک منبع پرداخت وارد کنید.',
        'shares.*.funding_source_id.required' => 'انتخاب منبع مالی الزامی است.',
        'shares.*.funding_source_id.exists' => 'منبع مالی انتخاب شده معتبر نیست.',
        'shares.*.percentage.required' => 'درصد تخصیص الزامی است.',
        'shares.*.percentage.numeric' => 'درصد تخصیص باید عدد باشد.',
        'shares.*.percentage.min' => 'درصد تخصیص باید حداقل 0.01 درصد باشد.',
        'shares.*.percentage.max' => 'درصد تخصیص نمی‌تواند بیش از 100 درصد باشد.',
        'shares.*.description.max' => 'توضیحات نمی‌تواند بیش از 1000 کاراکتر باشد.',
    ];

    public function mount()
    {
        $this->resetShares();
        $this->loadFundingSources();
    }

    /**
     * باز کردن مودال سهم‌بندی - این متد توسط رویداد Livewire فراخوانی می‌شود
     */
    public function openModal($params = null)
    {
        $this->resetErrorMessages();
        $this->resetShares();
        
        // لاگ کردن پارامترهای ورودی برای دیباگ
        Log::info('ShareAllocationModal::openModal called with:', ['params' => $params]);
        
        // تبدیل پارامترها به آرایه familyIds
        if (is_array($params)) {
            // پارامتر به صورت آرایه مستقیم آیدی‌ها
            $this->familyIds = $params;
        } elseif (is_numeric($params)) {
            // پارامتر یک آیدی منفرد
            $this->familyIds = [(int)$params];
        } elseif ($params === null) {
            // پارامتری ارسال نشده
            $this->familyIds = [];
        } else {
            // حالت‌های دیگر - تلاش برای تبدیل به آرایه
            $this->familyIds = [(int)$params];
        }
        
        // تبدیل همه آیدی‌ها به عدد صحیح
        $this->familyIds = array_map('intval', array_filter($this->familyIds));
        
        Log::info('Processed family IDs:', ['familyIds' => $this->familyIds]);
        
        // اگر هیچ خانواده‌ای انتخاب نشده، پیام خطا نمایش دهیم
        if (empty($this->familyIds)) {
            $this->errorMessage = 'هیچ خانواده‌ای انتخاب نشده است.';
            $this->showModal = true;
            return;
        }
        
        // ایجاد بیمه برای خانواده‌هایی که بیمه ندارند - فعلا غیرفعال شده تا از ایجاد بیمه پیش‌فرض جلوگیری شود
        // $this->createMissingInsurances();
        
        $this->showModal = true;
    }
    
    /**
     * ایجاد بیمه برای خانواده‌هایی که بیمه ندارند
     */
    protected function createMissingInsurances()
    {
        if (empty($this->familyIds)) {
            return;
        }
        
        // خانواده‌های انتخاب شده را بررسی می‌کنیم
        $families = Family::whereIn('id', $this->familyIds)->get();
        
        foreach ($families as $family) {
            // بررسی وجود بیمه فعال
            $hasInsurance = \App\Models\FamilyInsurance::where('family_id', $family->id)
                ->where(function($query) {
                    $query->whereNull('end_date')
                        ->orWhere('end_date', '>=', now());
                })
                ->exists();
            
            // اگر بیمه نداشت، یک بیمه پیش‌فرض ایجاد می‌کنیم
            // if (!$hasInsurance) {
            //     \App\Models\FamilyInsurance::create([
            //         'family_id' => $family->id,
            //         'insurance_type' => 'health',
            //         'insurance_payer' => 'mixed',
            //         'premium_amount' => 1000000, // مبلغ پیش‌فرض یک میلیون تومان
            //         'start_date' => now(),
            //         'end_date' => now()->addYear(),
            //     ]);
                
            //     Log::info("بیمه پیش‌فرض برای خانواده {$family->id} ایجاد شد.");
            // }
        }
    }

    public function loadFundingSources()
    {
        $this->fundingSources = FundingSource::where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetErrorMessages();
    }

    public function addShare()
    {
        $this->shares[] = [
            'funding_source_id' => '',
            'percentage' => '',
            'description' => '',
        ];
    }

    public function removeShare($index)
    {
        unset($this->shares[$index]);
        $this->shares = array_values($this->shares);
        $this->calculateTotalPercentage();
    }

    public function updated($name)
    {
        if (strpos($name, 'shares.') === 0 && strpos($name, '.percentage') !== false) {
            $this->calculateTotalPercentage();
        }
    }

    public function calculateTotalPercentage()
    {
        $this->totalPercentage = 0;
        foreach ($this->shares as $share) {
            if (isset($share['percentage']) && is_numeric($share['percentage'])) {
                $this->totalPercentage += (float)$share['percentage'];
            }
        }
        $this->totalPercentage = round($this->totalPercentage, 2);
    }

    public function allocateShares()
    {
        $this->resetErrorMessages();
        $this->isProcessing = true;
        
        Log::info('🚀 ShareAllocationModal::allocateShares - شروع فرایند تخصیص سهام', [
            'familyIds_count' => count($this->familyIds),
            'familyIds' => $this->familyIds,
            'shares' => $this->shares,
            'totalPercentage' => $this->totalPercentage
        ]);

        try {
            $this->validate();
            Log::info('✅ ShareAllocationModal::allocateShares - اعتبارسنجی فرم با موفقیت انجام شد');

            // بررسی مجموع درصدها
            $this->calculateTotalPercentage();
            Log::info('🔢 ShareAllocationModal::allocateShares - مجموع درصدها: ' . $this->totalPercentage);
            
            if (abs($this->totalPercentage - 100) > 0.01) {
                $this->errorMessage = 'جمع درصدها باید دقیقاً ۱۰۰٪ باشد.';
                $this->isProcessing = false;
                Log::warning('⚠️ ShareAllocationModal::allocateShares - خطا: مجموع درصدها دقیقاً 100% نیست', [
                    'totalPercentage' => $this->totalPercentage
                ]);
                return;
            }

            // بررسی خانواده‌های انتخاب شده
            if (empty($this->familyIds)) {
                $this->errorMessage = 'هیچ خانواده‌ای انتخاب نشده است.';
                $this->isProcessing = false;
                Log::warning('⚠️ ShareAllocationModal::allocateShares - خطا: هیچ خانواده‌ای انتخاب نشده است');
                return;
            }

            // دریافت خانواده‌های انتخاب شده
            $families = Family::whereIn('id', $this->familyIds)->get();
            Log::info('👪 ShareAllocationModal::allocateShares - خانواده‌های یافت شده', [
                'count' => $families->count(),
                'ids' => $families->pluck('id')->toArray()
            ]);
            
            // بررسی آیا همه خانواده‌های انتخاب شده یافت شده‌اند
            if ($families->count() != count($this->familyIds)) {
                Log::warning('⚠️ ShareAllocationModal::allocateShares - تعداد خانواده‌های یافت شده با تعداد آیدی‌های ارسالی مطابقت ندارد', [
                    'found' => $families->count(),
                    'expected' => count($this->familyIds),
                    'missing_ids' => array_diff($this->familyIds, $families->pluck('id')->toArray())
                ]);
            }
            
            // بررسی منابع مالی
            foreach ($this->shares as $index => $share) {
                if (empty($share['funding_source_id'])) {
                    Log::warning('⚠️ ShareAllocationModal::allocateShares - منبع مالی برای سهم شماره ' . ($index + 1) . ' مشخص نشده است');
                } else {
                    $source = FundingSource::find($share['funding_source_id']);
                    if (!$source) {
                        Log::warning('⚠️ ShareAllocationModal::allocateShares - منبع مالی با شناسه ' . $share['funding_source_id'] . ' یافت نشد');
                    } else {
                        Log::info('✅ ShareAllocationModal::allocateShares - منبع مالی معتبر است: ' . $source->name);
                    }
                }
            }

            // استخراج مقادیر مورد نیاز برای فراخوانی سرویس
            $payerType = 'funding_source'; // نوع پرداخت کننده
            $fundingSourceId = $this->shares[0]['funding_source_id'] ?? null; // شناسه منبع مالی از اولین سهم

            // ایجاد سهم‌ها توسط سرویس
            $shareService = new InsuranceShareService();
            Log::info('🔄 ShareAllocationModal::allocateShares - فراخوانی سرویس تخصیص سهام');

            $result = $shareService->allocate($families, $this->shares, $payerType, $fundingSourceId);
            Log::info('✅ ShareAllocationModal::allocateShares - نتیجه فراخوانی سرویس', [
                'created_shares_count' => $result['created_shares_count'],
                'errors' => $result['errors']
            ]);
            
            // بررسی نتیجه
            $createdShares = $result['shares'] ?? [];
            $errors = $result['errors'] ?? [];
            
            if (!empty($errors)) {
                $this->errorMessage = 'خطا در تخصیص سهم برای برخی خانواده‌ها: ' . implode(', ', $errors);
                Log::warning('⚠️ ShareAllocationModal::allocateShares - خطا در تخصیص سهام', [
                    'errors' => $errors
                ]);
            }
            
            // **این شرط اصلاح شده است**
            if ($result['created_shares_count'] > 0) {
                $this->successMessage = "سهم‌های بیمه با موفقیت ذخیره شدند!";
                Log::info('✅ ShareAllocationModal::allocateShares - سهام با موفقیت ذخیره شدند', [
                    'created_shares_count' => $result['created_shares_count']
                ]);
                
                // ارسال رویداد sharesAllocated
                Log::info('🔄 ShareAllocationModal::allocateShares - ارسال رویداد sharesAllocated');
                $this->dispatch('sharesAllocated');
                
                // بستن مودال
                $this->showModal = false;
            } else if (empty($this->errorMessage)) {
                $this->errorMessage = 'هیچ سهمی ایجاد نشد. ممکن است سهم‌ها قبلاً تخصیص داده شده باشند.';
                Log::warning('⚠️ ShareAllocationModal::allocateShares - هیچ سهمی ایجاد نشد');
            }
        } catch (\Exception $e) {
            Log::error('❌ ShareAllocationModal::allocateShares - خطا: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->errorMessage = 'خطا در تخصیص سهم: ' . $e->getMessage();
        }

        $this->isProcessing = false;
        Log::info('🏁 ShareAllocationModal::allocateShares - پایان فرایند تخصیص سهام');
    }

    public function resetShares()
    {
        $this->shares = [
            [
                'funding_source_id' => '',
                'percentage' => '',
                'description' => '',
            ]
        ];
        $this->totalPercentage = 0;
    }

    public function resetErrorMessages()
    {
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.insurance.share-allocation-modal');
    }
}