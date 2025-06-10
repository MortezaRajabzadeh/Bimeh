<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyFundingAllocation;
use App\Models\FundingSource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class FamilyFundingAllocationService
{
    /**
     * دریافت لیست تخصیص‌های بودجه با فیلتر
     */
    public function getAllocations(array $filters = [])
    {
        $query = FamilyFundingAllocation::with(['family.head', 'fundingSource', 'creator'])
            ->orderBy('created_at', 'desc');

        // فیلتر بر اساس خانواده
        if (!empty($filters['family_id'])) {
            $query->where('family_id', $filters['family_id']);
        }
        
        // فیلتر بر اساس آرایه‌ای از خانواده‌ها (برای جستجو)
        if (!empty($filters['family_ids']) && is_array($filters['family_ids'])) {
            $query->whereIn('family_id', $filters['family_ids']);
        }

        // فیلتر بر اساس منبع مالی
        if (!empty($filters['funding_source_id'])) {
            $query->where('funding_source_id', $filters['funding_source_id']);
        }

        // فیلتر بر اساس وضعیت
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate(15);
    }

    /**
     * محاسبه مبلغ حق بیمه برای یک خانواده
     * @param int $familyId
     * @return float
     */
    protected function getFamilyPremium($familyId)
    {
        $premium = \App\Models\FamilyInsurance::where('family_id', $familyId)
            ->sum('premium_amount');
        
        return $premium ?: 0;
    }

    /**
     * ایجاد تخصیص جدید
     */
    public function createAllocation(array $data)
    {
        return DB::transaction(function () use ($data) {
            // بررسی اعتبار مجموع درصدها
            $this->validatePercentageTotal($data['family_id'], $data['percentage']);

            // بررسی موجودی منبع مالی
            $this->validateFundingSourceAvailability($data['funding_source_id'], $data['amount'] ?? 0);

            // ایجاد تخصیص به صورت تأیید شده
            $allocation = FamilyFundingAllocation::create([
                'family_id' => $data['family_id'],
                'funding_source_id' => $data['funding_source_id'],
                'percentage' => $data['percentage'],
                'amount' => $data['amount'] ?? 0,
                'status' => FamilyFundingAllocation::STATUS_APPROVED,
                'description' => $data['description'] ?? null,
                'created_by' => Auth::id(),
                'approved_at' => now(),
                'approved_by' => Auth::id(),
            ]);

            // محاسبه مبلغ بر اساس درصد اگر مشخص نشده
            if (empty($data['amount']) && !empty($data['percentage'])) {
                $familyPremium = $this->getFamilyPremium($data['family_id']);
                if ($familyPremium > 0) {
                    $allocation->calculateAmount($familyPremium);
                    $allocation->save();
                }
            }

            return $allocation->load(['family', 'fundingSource']);
        });
    }

    /**
     * به‌روزرسانی تخصیص
     */
    public function updateAllocation(FamilyFundingAllocation $allocation, array $data)
    {
        return DB::transaction(function () use ($allocation, $data) {
            // بررسی اعتبار مجموع درصدها (با استثنای تخصیص جاری)
            if (isset($data['percentage'])) {
                $this->validatePercentageTotal(
                    $data['family_id'] ?? $allocation->family_id,
                    $data['percentage'],
                    $allocation->id
                );
            }

            // به‌روزرسانی فیلدها
            $allocation->update([
                'family_id' => $data['family_id'] ?? $allocation->family_id,
                'funding_source_id' => $data['funding_source_id'] ?? $allocation->funding_source_id,
                'percentage' => $data['percentage'] ?? $allocation->percentage,
                'amount' => $data['amount'] ?? $allocation->amount,
                'description' => $data['description'] ?? $allocation->description,
            ]);

            // محاسبه مجدد مبلغ اگر درصد تغییر کرده
            if (isset($data['percentage']) && empty($data['amount'])) {
                $familyPremium = $this->getFamilyPremium($allocation->family_id);
                if ($familyPremium > 0) {
                    $allocation->calculateAmount($familyPremium);
                    $allocation->save();
                }
            }

            return $allocation->load(['family', 'fundingSource']);
        });
    }

    /**
     * حذف تخصیص
     */
    public function deleteAllocation(FamilyFundingAllocation $allocation)
    {
        // بررسی امکان حذف (فقط تخصیصات در انتظار قابل حذف هستند)
        if ($allocation->status !== FamilyFundingAllocation::STATUS_PENDING) {
            throw new \Exception('فقط تخصیصات در انتظار قابل حذف هستند.');
        }

        return $allocation->delete();
    }

    /**
     * تایید تخصیص
     */
    public function approveAllocation(FamilyFundingAllocation $allocation)
    {
        if ($allocation->status !== FamilyFundingAllocation::STATUS_PENDING) {
            throw new \Exception('فقط تخصیصات در انتظار قابل تایید هستند.');
        }

        return $allocation->approve(Auth::id());
    }

    /**
     * علامت‌گذاری به عنوان پرداخت شده
     */
    public function markAllocationAsPaid(FamilyFundingAllocation $allocation)
    {
        if ($allocation->status !== FamilyFundingAllocation::STATUS_APPROVED) {
            throw new \Exception('فقط تخصیصات تایید شده قابل پرداخت هستند.');
        }

        return $allocation->markAsPaid();
    }

    /**
     * دریافت تخصیصات یک خانواده
     */
    public function getFamilyAllocations($familyId)
    {
        return FamilyFundingAllocation::where('family_id', $familyId)
            ->with(['fundingSource', 'creator', 'approver'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * جزئیات وضعیت تخصیص بودجه خانواده
     */
    public function getAllocationStatusDetails($familyId)
    {
        $status = FamilyFundingAllocation::getAllocationStatus($familyId);
        
        // اضافه کردن اطلاعات تکمیلی
        $family = Family::with('head')->find($familyId);
        if ($family) {
            // استفاده از کد خانواده و نام سرپرست
            $headName = optional($family->head)->first_name . ' ' . optional($family->head)->last_name;
            $status['family_name'] = $family->family_code . ' - ' . $headName;
            $familyPremium = $this->getFamilyPremium($familyId);
            $status['total_premium'] = $familyPremium;
            $status['remaining_amount'] = $familyPremium - $status['total_amount'];
            $status['remaining_percentage'] = 100 - $status['total_percentage'];
        }

        return $status;
    }

    /**
     * بررسی اعتبار مجموع درصدها
     */
    private function validatePercentageTotal($familyId, $newPercentage, $excludeId = null)
    {
        $currentTotal = FamilyFundingAllocation::where('family_id', $familyId)
            ->when($excludeId, function ($query, $excludeId) {
                return $query->where('id', '!=', $excludeId);
            })
            ->sum('percentage');

        if (($currentTotal + $newPercentage) > 100) {
            throw new \Exception('مجموع درصد تخصیص‌ها نمی‌تواند از ۱۰۰٪ بیشتر باشد.');
        }
    }

    /**
     * بررسی موجودی منبع مالی
     */
    private function validateFundingSourceAvailability($fundingSourceId, $requestedAmount)
    {
        $fundingSource = FundingSource::find($fundingSourceId);
        
        if (!$fundingSource || !$fundingSource->is_active) {
            throw new \Exception('منبع مالی انتخابی فعال نیست.');
        }

        // ممکن است ستون annual_budget یا budget داشته باشیم
        $availableBudget = $fundingSource->annual_budget ?? $fundingSource->budget ?? 0;
        
        if ($availableBudget > 0) {
            $allocatedAmount = FamilyFundingAllocation::where('funding_source_id', $fundingSourceId)
                ->where('status', '!=', FamilyFundingAllocation::STATUS_PENDING)
                ->sum('amount');

            $remainingBudget = $availableBudget - $allocatedAmount;

            if ($requestedAmount > $remainingBudget) {
                throw new \Exception('بودجه منبع مالی کافی نیست. مبلغ باقی‌مانده: ' . number_format($remainingBudget) . ' تومان');
            }
        }
    }

    /**
     * ایجاد تخصیص برای تمام خانواده‌های در انتظار صدور
     */
    public function createAllocationForAllFamilies(array $data)
    {
        return DB::transaction(function () use ($data) {
            // بررسی موجودی منبع مالی (به صورت کلی)
            $this->validateFundingSourceAvailability($data['funding_source_id'], 0);

            // دریافت کدهای خانواده‌های آپلود شده از سشن
            $familyCodes = session('last_imported_family_codes', []);
            
            // دریافت آخرین لاگ آپلود
            $lastImportLog = null;
            
            if (empty($familyCodes)) {
                // اگر کدهای خانواده در سشن نباشد، از آخرین لاگ آپلود استفاده می‌کنیم
                $lastImportLog = \App\Models\InsuranceImportLog::latest()->first();
                if ($lastImportLog) {
                    $familyCodes = is_array($lastImportLog->family_codes) ? $lastImportLog->family_codes : json_decode($lastImportLog->family_codes, true);
                    
                    // بررسی آیا قبلاً برای این فایل و منبع مالی با همین درصد تخصیص انجام شده است
                    if ($this->hasAllocationForImportLog($lastImportLog->id, $data['funding_source_id'], $data['percentage'])) {
                        throw new \Exception('برای این فایل اکسل قبلاً با همین درصد و منبع مالی تخصیص انجام شده است. لطفاً از درصد یا منبع مالی متفاوتی استفاده کنید.');
                    }
                }
            } else {
                // پیدا کردن لاگ آپلود مرتبط با کدهای خانواده در سشن
                $lastImportLog = \App\Models\InsuranceImportLog::whereJsonContains('family_codes', array_values($familyCodes)[0])
                    ->orWhere(function($query) use ($familyCodes) {
                        foreach($familyCodes as $code) {
                            $query->orWhereRaw("JSON_SEARCH(family_codes, 'one', ?) IS NOT NULL", [$code]);
                        }
                    })
                    ->latest()
                    ->first();
            }
            
            Log::info("تعداد کدهای خانواده برای تخصیص بودجه: " . count($familyCodes));
            
            // یافتن همه خانواده‌های آپلود شده با وضعیت insured
            $families = Family::whereIn('family_code', $familyCodes)
                ->where('status', 'insured')
                ->get();
            
            Log::info("تعداد خانواده‌های بیمه شده از فایل آپلود شده برای تخصیص بودجه: " . $families->count());
            
            if ($families->isEmpty()) {
                throw new \Exception('هیچ خانواده بیمه شده‌ای از فایل آپلود شده برای تخصیص بودجه یافت نشد.');
            }

            // بررسی اگر برای این خانواده‌ها قبلاً با همین منبع مالی و درصد تخصیص انجام شده است
            $percentage = floatval($data['percentage']);
            $fundingSourceId = intval($data['funding_source_id']);
            
            $existingAllocationCount = FamilyFundingAllocation::whereIn('family_id', $families->pluck('id'))
                ->where('funding_source_id', $fundingSourceId)
                ->where('percentage', $percentage)
                ->count();
                
            if ($existingAllocationCount > 0) {
                throw new \Exception("برای " . $existingAllocationCount . " خانواده از این فایل قبلاً تخصیص با درصد " . $percentage . "% از همین منبع مالی انجام شده است.");
            }

            $allocations = [];
            $totalAllocated = 0;
            $errors = [];
            
            // بررسی منبع مالی
            $fundingSource = FundingSource::find($fundingSourceId);
            if (!$fundingSource) {
                throw new \Exception('منبع مالی انتخاب شده معتبر نیست.');
            }
            
            // محاسبه کل مبلغ حق بیمه برای خانواده‌های انتخاب شده
            $familyIds = $families->pluck('id')->toArray();
            
            $totalFamilyPremium = \App\Models\FamilyInsurance::whereIn('family_id', $familyIds)
                ->sum('premium_amount');
                
            Log::info("مجموع حق بیمه برای {$families->count()} خانواده: {$totalFamilyPremium}");
            
            // محاسبه مبلغ تخصیص کلی
            $totalAllocationAmount = round(($percentage / 100) * $totalFamilyPremium);
            Log::info("مبلغ کل تخصیص ({$percentage}%): {$totalAllocationAmount}");
            
            // بررسی موجودی منبع مالی
            $this->validateFundingSourceAvailability($fundingSourceId, $totalAllocationAmount);
            
            // ایجاد یک تراکنش کلی برای این تخصیص
            $transaction = \App\Models\FundingTransaction::create([
                'funding_source_id' => $fundingSourceId,
                'amount' => -$totalAllocationAmount,  // منفی چون برداشت از منبع مالی است
                'type' => 'withdrawal',
                'description' => "تخصیص بودجه {$percentage}% برای {$families->count()} خانواده",
                'status' => 'approved',
                'created_by' => Auth::id(),
            ]);
            
            // پاک کردن کش بودجه پس از ایجاد تراکنش
            \Illuminate\Support\Facades\Cache::forget('remaining_budget');
            
            // ایجاد تخصیص برای هر خانواده
            foreach ($families as $family) {
                try {
                    // محاسبه مبلغ تخصیص برای این خانواده
                    $familyInsurance = \App\Models\FamilyInsurance::where('family_id', $family->id)->first();
                    
                    if (!$familyInsurance) {
                        Log::warning("هیچ اطلاعات بیمه‌ای برای خانواده {$family->id} یافت نشد.");
                        continue;
                    }
                    
                    $familyPremium = $familyInsurance->premium_amount;
                    $allocationAmount = round(($percentage / 100) * $familyPremium);
                    
                    Log::info("مبلغ تخصیص برای خانواده {$family->id}: {$allocationAmount} (حق بیمه: {$familyPremium})");
                    
                    if ($allocationAmount <= 0) {
                        Log::warning("مبلغ تخصیص برای خانواده {$family->id} صفر یا منفی است.");
                        continue;
                    }
                    
                    // ایجاد تخصیص
                    $allocation = FamilyFundingAllocation::create([
                        'family_id' => $family->id,
                        'funding_source_id' => $fundingSourceId,
                        'import_log_id' => $lastImportLog ? $lastImportLog->id : null,
                        'transaction_id' => $transaction->id,
                        'percentage' => $percentage,
                        'amount' => $allocationAmount,
                        'description' => $data['description'] ?? null,
                        'status' => FamilyFundingAllocation::STATUS_APPROVED,
                        'created_by' => Auth::id(),
                        'approved_at' => now(),
                        'approved_by' => Auth::id(),
                    ]);
                    
                    $allocations[] = $allocation;
                    $totalAllocated += $allocationAmount;
                    
                } catch (\Exception $e) {
                    Log::error("خطا در ایجاد تخصیص برای خانواده {$family->id}: " . $e->getMessage());
                    $errors[] = "خانواده {$family->family_code}: " . $e->getMessage();
                }
            }
            
            // اطمینان از اینکه مجموع تخصیص‌ها با کل مبلغ مطابقت دارد
            if (abs($totalAllocated - $totalAllocationAmount) > 100) { // تلرانس خطای 100 تومان
                Log::warning("تفاوت بین مجموع تخصیص‌های انفرادی ({$totalAllocated}) و کل مبلغ تخصیص ({$totalAllocationAmount})");
            }
            
            if (!empty($errors)) {
                Log::warning("خطاهای تخصیص بودجه: " . implode(", ", $errors));
            }
            
            Log::info("تعداد تخصیص‌های موفق: " . count($allocations));
            
            return $allocations;
        });
    }

    /**
     * بررسی می‌کند آیا قبلاً برای این فایل آپلود و منبع مالی با درصد مشخص تخصیص انجام شده است
     * 
     * @param int $importLogId شناسه فایل آپلود
     * @param int $fundingSourceId شناسه منبع مالی
     * @param float $percentage درصد تخصیص
     * @return bool
     */
    private function hasAllocationForImportLog($importLogId, $fundingSourceId, $percentage)
    {
        // فرض می‌کنیم که اگر آخرین فایل آپلود شده است، 
        // برای تمام خانواده‌های آن آپلود با این منبع مالی و درصد تخصیص انجام شده است
        $lastImportLog = \App\Models\InsuranceImportLog::find($importLogId);
        if (!$lastImportLog) {
            return false;
        }
        
        $familyCodes = is_array($lastImportLog->family_codes) ? $lastImportLog->family_codes : json_decode($lastImportLog->family_codes, true);
        if (empty($familyCodes)) {
            return false;
        }
        
        // یافتن همه خانواده‌های آپلود شده
        $families = Family::whereIn('family_code', $familyCodes)->get();
        if ($families->isEmpty()) {
            return false;
        }
        
        // بررسی وجود تخصیص با همین منبع مالی و درصد برای هر یک از خانواده‌ها
        $existingAllocationCount = FamilyFundingAllocation::whereIn('family_id', $families->pluck('id'))
            ->where('funding_source_id', $fundingSourceId)
            ->where('percentage', $percentage)
            ->count();
            
        // اگر حداقل برای یک خانواده تخصیص انجام شده باشد، به عنوان تخصیص قبلی در نظر می‌گیریم
        return $existingAllocationCount > 0;
    }
}