<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FundingSource;
use App\Models\FamilyInsurance;
use App\Models\InsuranceShare;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class InsuranceShareService
{
    /**
     * تخصیص سهم‌بندی به چندین خانواده
     *
     * @param Collection $families خانواده‌های انتخاب شده
     * @param array $shares آرایه‌ای از سهم‌ها با فیلدهای funding_source_id، percentage و description
     * @param int|null $importLogId شناسه لاگ ایمپورت (اختیاری)
     * @return array آرایه‌ای از سهم‌های ایجاد شده
     * @throws \Exception در صورت بروز خطا
     */
    public function allocate(Collection $families, array $shares, ?int $importLogId = null): array
    {
        // بررسی اعتبار مجموع درصدها
        $totalPercentage = collect($shares)->sum('percentage');
        if (abs($totalPercentage - 100) > 0.01) {
            throw new \Exception('جمع درصدها باید دقیقاً ۱۰۰٪ باشد.');
        }

        return DB::transaction(function () use ($families, $shares, $importLogId) {
            $createdShares = [];
            $errors = [];
            
            foreach ($families as $family) {
                try {
                    // پیدا کردن یا ایجاد بیمه برای خانواده
                    $familyInsurances = $this->getFamilyInsurances($family->id);
                    
                    if ($familyInsurances->isEmpty()) {
                        Log::warning("خانواده با شناسه {$family->id} بیمه فعال ندارد.");
                        $errors[] = "خانواده با کد {$family->family_code} بیمه فعال ندارد.";
                        continue;
                    }
                    
                    // از آخرین بیمه فعال استفاده می‌کنیم
                    $familyInsurance = $familyInsurances->sortByDesc('start_date')->first();
                    
                    // تعیین مبلغ حق بیمه
                    $premiumAmount = $familyInsurance->premium_amount ?: 0;
                    
                    // برای هر سهم در آرایه ورودی
                    foreach ($shares as $shareData) {
                        // بررسی تکراری نبودن سهم
                        $existingShare = InsuranceShare::where('family_insurance_id', $familyInsurance->id)
                            ->where('percentage', $shareData['percentage']);
                        
                        // اگر funding_source_id در داده وجود دارد و در جدول هم ستون وجود دارد
                        if (isset($shareData['funding_source_id']) && Schema::hasColumn('insurance_shares', 'funding_source_id')) {
                            $existingShare->where('funding_source_id', $shareData['funding_source_id']);
                        }
                        
                        $existingShare = $existingShare->first();
                        
                        if ($existingShare) {
                            Log::info("سهم تکراری برای خانواده {$family->id} و منبع مالی {$shareData['funding_source_id']} با درصد {$shareData['percentage']} یافت شد.");
                            continue;
                        }
                        
                        // ایجاد سهم جدید
                        $share = new InsuranceShare();
                        $share->family_insurance_id = $familyInsurance->id;
                        $share->percentage = $shareData['percentage'];
                        $share->description = $shareData['description'] ?? null;
                        
                        // پیش‌فرض برای پارامترهای پرداخت‌کننده
                        $share->payer_type = $shareData['payer_type'] ?? 'other';

                        // اضافه کردن created_by اگر ستون وجود دارد
                        if (Schema::hasColumn('insurance_shares', 'created_by')) {
                            $share->created_by = Auth::id();
                        }

                        // اضافه کردن import_log_id اگر ستون وجود دارد و مقدار هم ارسال شده است
                        if (Schema::hasColumn('insurance_shares', 'import_log_id') && $importLogId !== null) {
                            $share->import_log_id = $importLogId;
                        }
                        
                        // اضافه کردن funding_source_id اگر در داده‌ها وجود دارد و در جدول هم این ستون وجود دارد
                        if (isset($shareData['funding_source_id']) && Schema::hasColumn('insurance_shares', 'funding_source_id')) {
                            $share->funding_source_id = $shareData['funding_source_id'];
                            
                            // اضافه کردن اطلاعات پرداخت‌کننده از منبع مالی
                            $fundingSource = FundingSource::find($shareData['funding_source_id']);
                            if ($fundingSource) {
                                $share->payer_type = $fundingSource->source_type ?? 'other';
                                $share->payer_name = $fundingSource->name;
                            } else {
                                // اگر منبع مالی یافت نشد، از مقدار پیش‌فرض یا ارسالی استفاده کن
                                $share->payer_name = $shareData['payer_name'] ?? 'نامشخص';
                            }
                        } else {
                            // اگر funding_source_id تنظیم نشده، از مقدار پیش‌فرض یا ارسالی استفاده کن
                            $share->payer_name = $shareData['payer_name'] ?? 'نامشخص';
                        }
                        
                        // محاسبه مبلغ بر اساس درصد
                        $share->amount = ($share->percentage / 100) * $premiumAmount;
                        
                        $share->save();
                        $createdShares[] = $share;
                    }
                } catch (\Exception $e) {
                    Log::error("خطا در تخصیص سهم: " . $e->getMessage());
                    $errors[] = "خطا در تخصیص سهم برای خانواده با کد {$family->family_code}: " . $e->getMessage();
                }
            }
            
            return [
                'shares' => $createdShares,
                'errors' => $errors
            ];
        });
    }

    /**
     * دریافت بیمه‌های خانواده
     * @param int $familyId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getFamilyInsurances(int $familyId)
    {
        return FamilyInsurance::where('family_id', $familyId)
            ->where(function($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->get();
    }

    /**
     * محاسبه مبلغ حق بیمه برای یک خانواده
     * @param int $familyId
     * @return float
     */
    protected function getFamilyPremium(int $familyId): float
    {
        $premium = FamilyInsurance::where('family_id', $familyId)
            ->sum('premium_amount');
        
        return $premium ?: 0;
    }

    /**
     * دریافت خلاصه سهم‌بندی برای یک خانواده
     * @param int $familyId
     * @return array
     */
    public function getSummary(int $familyId): array
    {
        // یافتن بیمه‌های خانواده
        $familyInsurances = FamilyInsurance::where('family_id', $familyId)->get();
        $familyInsuranceIds = $familyInsurances->pluck('id')->toArray();
        
        // یافتن سهم‌های مرتبط با بیمه‌های خانواده
        $shares = InsuranceShare::whereIn('family_insurance_id', $familyInsuranceIds)
            ->with('fundingSource')
            ->get();
        
        $totalPercentage = $shares->sum('percentage');
        $totalAmount = $shares->sum('amount');
        
        return [
            'shares' => $shares,
            'total_percentage' => $totalPercentage,
            'total_amount' => $totalAmount,
            'formatted_total_amount' => number_format($totalAmount) . ' تومان',
            'is_fully_allocated' => abs($totalPercentage - 100) < 0.01,
        ];
    }
} 