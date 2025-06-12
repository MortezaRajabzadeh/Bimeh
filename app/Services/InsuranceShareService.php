<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyInsurance;
use App\Models\InsuranceShare;
use App\Models\ShareAllocationLog; // مدل جدید اضافه شده
use App\Services\InsuranceImportLogger;
use App\Models\FamilyStatusLog;
use App\Enums\InsuranceWizardStep;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class InsuranceShareService
{
    /**
     * مرحله ۱: تخصیص سهم و ایجاد یک لاگ گروهی برای آن
     */
    public function allocate(Collection $families, array $shares, string $payerType, ?int $fundingSourceId = null): array
    {
            'families_count' => $families->count(),
            'shares' => $shares,
            'payer_type' => $payerType,
            'funding_source_id' => $fundingSourceId
        ]);
    
        // اعتبارسنجی درصدهای سهام
        $totalPercentage = collect($shares)->sum('percentage');
        if (abs($totalPercentage - 100) > 0.01) {
            throw new \Exception("مجموع درصدهای سهام باید 100 درصد باشد. مجموع فعلی: {$totalPercentage}%");
        }
    
        $createdShares = []; // آرایه‌ای برای نگهداری سهم‌های ایجاد شده
        $errors = [];
    
        DB::transaction(function () use ($families, $shares, &$createdShares, &$errors, $payerType, $fundingSourceId) {
            foreach ($families as $family) {
                try {
    
                    // ایجاد رکورد بیمه نیمه‌کاره
                    $familyInsurance = FamilyInsurance::create([
                        'family_id' => $family->id,
                        'insurance_type' => 'تکمیلی',
                        'premium_amount' => 0,
                        'start_date' => now(),
                        'end_date' => now()->addYear(),
                        'status' => 'awaiting_upload',
                        'payer_type' => $payerType,
                        'funding_source_id' => $fundingSourceId,
                    ]);
    
    
                    // ایجاد رکوردهای سهم
                    foreach ($shares as $shareData) {
                        if ($shareData['percentage'] > 0) {
                            $fundingSource = null;
                            if ($fundingSourceId) {
                                $fundingSource = \App\Models\FundingSource::find($fundingSourceId);
                            }
    
                            $payerData = [
                                'family_insurance_id' => $familyInsurance->id,
                                'percentage' => $shareData['percentage'],
                                'amount' => 0,
                            ];
    
                            if ($fundingSource) {
                                $payerData['payer_name'] = $fundingSource->name;
                                
                                if ($fundingSource->type === 'organization') {
                                    $payerData['payer_organization_id'] = $fundingSource->source_id ?? null;
                                } elseif ($fundingSource->type === 'user') {
                                    $payerData['payer_user_id'] = $fundingSource->source_id ?? null;
                                }
                                
                                if (isset($shareData['payer_type_id'])) {
                                    $payerData['payer_type_id'] = $shareData['payer_type_id'];
                                }
                            }
    
                            $share = InsuranceShare::create($payerData);
                            $createdShares[] = $share; // رکورد ایجاد شده را به آرایه اضافه می‌کنیم
                            
                        }
                    }
                    
    
                } catch (\Exception $e) {
                    $errorMessage = "خطا در تخصیص سهام برای خانواده {$family->family_code}: " . $e->getMessage();
                    $errors[] = $errorMessage;
                    continue;
                }
            }
    
            if (!empty($errors)) {
                throw new \Exception("خطا در حین پردازش تخصیص سهم رخ داد.");
            }
        });
    
        // **این بخش اصلاح شده است**
        // به جای یک شمارنده جداگانه، تعداد اعضای آرایه createdShares را برمی‌گردانیم
        return [
            'shares' => $createdShares,
            'created_shares_count' => count($createdShares),
            'errors' => $errors,
        ];
    }

    /**
     * Get family insurances for processing
     */
    public function getFamilyInsurances(Collection $families): Collection
    {
        return FamilyInsurance::whereIn('family_id', $families->pluck('id'))
            ->where('status', '!=', 'mixed')
            ->where('premium_amount', '>', 0)
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->get();
    }

    /**
     * Stage 2: Complete insurance from Excel upload
     */
    public function completeInsuranceFromExcel(string $filePath): array
    {

        // Read Excel file
        $imported = Excel::toCollection(null, $filePath);
        
        if (!isset($imported[0])) {
            throw new \Exception('فایل اکسل آپلود شده فاقد داده است یا ساختار آن صحیح نیست.');
        }

        $rows = $imported[0]->toArray();
        $totalAmountForThisBatch = 0;
        $results = [];

        DB::transaction(function () use ($rows, &$results, &$totalAmountForThisBatch) {
            foreach ($rows as $row) {
                // منطق پردازش هر ردیف اینجا قرار می‌گیرد
                // پس از update کردن insurance و recalculate کردن share amounts:
                // if (isset($insurance)) {
                //     $totalAmountForThisBatch += $insurance->premium_amount;
                // }
            }

            // ۴. به‌روزرسانی لاگ تخصیص سهم مربوطه با مبلغ نهایی
            if (isset($rows[1])) {
                $firstFamilyCode = trim($rows[1][0] ?? '');
                $firstFamily = Family::where('family_code', $firstFamilyCode)->first();
                if ($firstFamily) {
                    // آخرین لاگ مربوط به این خانواده را پیدا کن
                                                    ->latest()
                                                    ->first();
                    if ($relatedLog) {
                        $relatedLog->update([
                            'total_amount' => $totalAmountForThisBatch,
                            'status' => 'completed'
                        ]);
                    }
                }
            }
        });
        
        return $results;
    }

    /**
     * Recalculate share amounts based on final premium
     */
    private function recalculateShareAmounts(int $familyInsuranceId, int $premiumAmount): void
    {
        $shares = InsuranceShare::where('family_insurance_id', $familyInsuranceId)->get();
        
        foreach ($shares as $share) {
            $amount = ($premiumAmount * $share->percentage) / 100;
            $share->update(['amount' => $amount]);
            
        }
    }

    /**
     * Parse Jalali or Gregorian date
     */
    private function parseJalaliOrGregorianDate($dateString)
    {
        // Add your date parsing logic here
        // This is a placeholder - implement based on your existing date parsing logic
        return now(); // Temporary return
    }
}
