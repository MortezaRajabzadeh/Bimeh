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
        
        // ایجاد بیمه برای خانواده‌هایی که بیمه ندارند
        $this->createMissingInsurances();
        
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
            if (!$hasInsurance) {
                \App\Models\FamilyInsurance::create([
                    'family_id' => $family->id,
                    'insurance_type' => 'health',
                    'insurance_payer' => 'mixed',
                    'premium_amount' => 1000000, // مبلغ پیش‌فرض یک میلیون تومان
                    'start_date' => now(),
                    'end_date' => now()->addYear(),
                ]);
                
                Log::info("بیمه پیش‌فرض برای خانواده {$family->id} ایجاد شد.");
            }
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

        try {
            $this->validate();

            // بررسی مجموع درصدها
            $this->calculateTotalPercentage();
            if (abs($this->totalPercentage - 100) > 0.01) {
                $this->errorMessage = 'جمع درصدها باید دقیقاً ۱۰۰٪ باشد.';
                $this->isProcessing = false;
                return;
            }

            // بررسی خانواده‌های انتخاب شده
            if (empty($this->familyIds)) {
                $this->errorMessage = 'هیچ خانواده‌ای انتخاب نشده است.';
                $this->isProcessing = false;
                return;
            }

            // دریافت خانواده‌های انتخاب شده
            $families = Family::whereIn('id', $this->familyIds)->get();
            
            // ایجاد سهم‌ها توسط سرویس
            $shareService = new InsuranceShareService();
            $result = $shareService->allocate($families, $this->shares);
            
            // بررسی نتیجه
            $createdShares = $result['shares'] ?? [];
            $errors = $result['errors'] ?? [];
            
            if (!empty($errors)) {
                $this->errorMessage = 'خطا در تخصیص سهم برای برخی خانواده‌ها: ' . implode(', ', $errors);
            }
            
            if (count($createdShares) > 0) {
                $this->successMessage = "سهم‌های بیمه با موفقیت ذخیره شدند!";
                $this->dispatch('sharesAllocated');
                
                // بستن مودال
                $this->showModal = false;
                
                // هدایت به تب "در انتظار صدور" در همان صفحه
                $this->dispatch('switchToReviewingTab');
            } else if (empty($this->errorMessage)) {
                $this->errorMessage = 'هیچ سهمی ایجاد نشد. ممکن است سهم‌ها قبلاً تخصیص داده شده باشند.';
            }
        } catch (\Exception $e) {
            Log::error('خطا در تخصیص سهم: ' . $e->getMessage());
            $this->errorMessage = 'خطا در تخصیص سهم: ' . $e->getMessage();
        }

        $this->isProcessing = false;
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