<?php

namespace App\Services;

use App\Models\Family;
use App\Models\FamilyInsurance;
use App\Models\InsuranceShare;
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
     * Stage 1: Allocate shares and create placeholder insurance records
     */
    public function allocate(Collection $families, array $shares, string $payerType, ?int $fundingSourceId = null): array
    {
        Log::info('🚀 شروع تخصیص سهام بیمه', [
            'families_count' => $families->count(),
            'shares' => $shares,
            'payer_type' => $payerType,
            'funding_source_id' => $fundingSourceId
        ]);
    
        // اعتبارسنجی درصدهای سهام
        $totalPercentage = collect($shares)->sum('percentage');
        if ($totalPercentage != 100) {
            throw new \Exception("مجموع درصدهای سهام باید 100 درصد باشد. مجموع فعلی: {$totalPercentage}%");
        }
    
        $createdShares = [];
        $errors = [];
        $createdCount = 0;
        $errorCount = 0;
    
        // Get funding source details if provided
        $fundingSource = null;
        if ($fundingSourceId) {
            $fundingSource = \App\Models\FundingSource::find($fundingSourceId);
        }
    
        DB::beginTransaction();
        try {
            foreach ($families as $family) {
                try {
                    Log::info("📋 پردازش خانواده {$family->family_code} (ID: {$family->id})");
    
                    // Stage 1: Create placeholder FamilyInsurance record
                    $familyInsurance = FamilyInsurance::create([
                        'family_id' => $family->id,
                        'insurance_type' => 'تکمیلی', // Default type
                        'premium_amount' => 0, // Placeholder amount
                        'start_date' => now(),
                        'end_date' => now()->addYear(),
                        'status' => 'awaiting_upload', // Placeholder status
                        'payer_type' => $payerType,
                        'funding_source_id' => $fundingSourceId,
                    ]);
    
                    Log::info("✅ رکورد بیمه placeholder برای خانواده {$family->family_code} ایجاد شد (ID: {$familyInsurance->id})");
    
                    // Create InsuranceShare records - FIXED to use correct schema
                    foreach ($shares as $shareData) {
                        if ($shareData['percentage'] > 0) {
                            // Prepare payer data based on funding source
                            $payerData = [
                                'family_insurance_id' => $familyInsurance->id,
                                'percentage' => $shareData['percentage'],
                                'amount' => 0, // Will be calculated later when premium is known
                            ];
    
                            // Set payer information based on funding source
                            if ($fundingSource) {
                                $payerData['payer_name'] = $fundingSource->name;
                                
                                // Determine payer type and set appropriate ID
                                if ($fundingSource->type === 'organization') {
                                    $payerData['payer_organization_id'] = $fundingSource->source_id ?? null;
                                } elseif ($fundingSource->type === 'user') {
                                    $payerData['payer_user_id'] = $fundingSource->source_id ?? null;
                                }
                                
                                // Set payer_type_id if available
                                if (isset($shareData['payer_type_id'])) {
                                    $payerData['payer_type_id'] = $shareData['payer_type_id'];
                                }
                            }
    
                            $share = InsuranceShare::create($payerData);
    
                            $createdShares[] = $share;
                            Log::info("📊 سهم با نام پرداخت‌کننده {$payerData['payer_name']} و درصد {$shareData['percentage']}% برای خانواده {$family->family_code} ایجاد شد");
                        }
                    }
    
                    $createdCount++;
                    Log::info("✅ تخصیص سهام برای خانواده {$family->family_code} با موفقیت انجام شد");
    
                } catch (\Exception $e) {
                    $errorMessage = "خطا در تخصیص سهام برای خانواده {$family->family_code}: " . $e->getMessage();
                    $errors[] = $errorMessage;
                    $errorCount++;
                    Log::error("❌ " . $errorMessage);
                }
            }
    
            // Update InsuranceImportLogger status
            $status = $errorCount > 0 ? 'shares_allocated_with_errors' : 'shares_allocated_pending_excel';
            
            DB::commit();
            Log::info("✅ تخصیص سهام با موفقیت انجام شد", [
                'created_count' => $createdCount,
                'error_count' => $errorCount,
                'status' => $status
            ]);
    
            return [
                'shares' => $createdShares,
                'errors' => $errors,
                'created_count' => $createdCount,
                'error_count' => $errorCount,
                'status' => $status
            ];
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ خطا در تخصیص سهام: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Stage 2: Complete insurance from Excel upload
     */
    public function completeInsuranceFromExcel(string $filePath): array
    {
        Log::info('⏳ شروع تکمیل بیمه از فایل اکسل: ' . $filePath);

        // Read Excel file
        $imported = Excel::toCollection(null, $filePath);
        
        if (!isset($imported[0])) {
            throw new \Exception('فایل اکسل آپلود شده فاقد داده است یا ساختار آن صحیح نیست.');
        }
        
        $rows = $imported[0];
        Log::info('✅ اکسل با موفقیت خوانده شد. تعداد کل ردیف‌ها: ' . count($rows));

        // Create import log
        $filename = basename($filePath);
        $importLog = InsuranceImportLogger::createLog($filename, count($rows));
        
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $validInsuranceTypes = ['تکمیلی', 'درمانی', 'عمر', 'حوادث', 'سایر', 'تامین اجتماعی'];
        $totalInsuranceAmount = 0;
        $familiesUpdated = [];
        $familyCodes = [];
        $updatedFamilyCodes = [];
        $createdFamilyCodes = [];
        $batchId = 'excel_upload_' . time() . '_' . $importLog->id;

        DB::beginTransaction();
        try {
            foreach ($rows as $i => $row) {
                try {
                    // Skip header or example rows
                    if ($i === 0 && (empty($row[0]) || stripos($row[0], 'مثال') !== false || stripos($row[0], 'کد') !== false)) {
                        Log::info('🔄 رد کردن ردیف سرتیتر: ' . json_encode($row));
                        continue;
                    }
                    
                    // Extract row data
                    $familyCode = isset($row[0]) ? trim($row[0]) : '';
                    $insuranceType = isset($row[1]) ? trim($row[1]) : '';
                    $premiumAmount = isset($row[2]) ? trim($row[2]) : '';
                    $startDate = isset($row[3]) ? trim($row[3]) : '';
                    $endDate = isset($row[4]) ? trim($row[4]) : '';
                    
                    Log::info("📄 پردازش ردیف {$i}: کد خانواده={$familyCode}, نوع بیمه={$insuranceType}, مبلغ={$premiumAmount}");
                    
                    // Validate family code
                    if (empty($familyCode)) {
                        throw new \Exception("کد خانواده خالی است");
                    }
                    
                    // Find family
                    $family = Family::where('family_code', $familyCode)->first();
                    if (!$family) {
                        throw new \Exception("خانواده با کد {$familyCode} یافت نشد");
                    }
                    
                    Log::info("✅ ردیف {$i}: خانواده با کد {$familyCode} و آیدی {$family->id} یافت شد");
                    
                    // Validate insurance type
                    if (empty($insuranceType) || !in_array($insuranceType, $validInsuranceTypes)) {
                        if (empty($insuranceType)) {
                            $insuranceType = 'تکمیلی';
                            Log::info("🔄 ردیف {$i}: نوع بیمه خالی است - استفاده از نوع پیش‌فرض: {$insuranceType}");
                        } else {
                            Log::warning("⚠️ ردیف {$i}: نوع بیمه '{$insuranceType}' معتبر نیست - استفاده از نوع پیش‌فرض: تکمیلی");
                            $insuranceType = 'تکمیلی';
                        }
                    }
                    
                    // Validate premium amount
                    $premiumAmount = preg_replace('/[^\d]/', '', $premiumAmount);
                    if (empty($premiumAmount)) {
                        $premiumAmount = 0;
                        Log::info("🔄 ردیف {$i}: مبلغ بیمه خالی است - استفاده از مقدار پیش‌فرض: 0");
                    } else {
                        $premiumAmount = intval($premiumAmount);
                        Log::info("✅ ردیف {$i}: مبلغ بیمه: {$premiumAmount}");
                    }
                    
                    // Parse dates
                    try {
                        if (empty($startDate)) {
                            $startDate = now();
                            Log::info("🔄 ردیف {$i}: تاریخ شروع خالی است - استفاده از تاریخ امروز");
                        } else {
                            $startDate = $this->parseJalaliOrGregorianDate($startDate);
                            Log::info("✅ ردیف {$i}: تاریخ شروع: {$startDate}");
                        }
                    
                        if (empty($endDate)) {
                            $endDate = now()->addYear();
                            Log::info("🔄 ردیف {$i}: تاریخ پایان خالی است - استفاده از یک سال بعد");
                        } else {
                            $endDate = $this->parseJalaliOrGregorianDate($endDate);
                            Log::info("✅ ردیف {$i}: تاریخ پایان: {$endDate}");
                        }
                    } catch (\Exception $e) {
                        throw new \Exception("خطا در تبدیل تاریخ: " . $e->getMessage());
                    }
                    
                    // Find existing placeholder insurance record with status 'awaiting_upload'
                    $insurance = FamilyInsurance::where('family_id', $family->id)
                        ->where('status', 'awaiting_upload')
                        ->first();
                    
                    $wasRecentlyCreated = false;
                    
                    if ($insurance) {
                        // Update existing placeholder record
                        $insurance->update([
                            'insurance_type' => $insuranceType,
                            'premium_amount' => $premiumAmount,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'status' => 'insured'
                        ]);
                        
                        // Recalculate share amounts based on final premium
                        $this->recalculateShareAmounts($insurance->id, $premiumAmount);
                        
                        Log::info("✅ ردیف {$i}: رکورد بیمه placeholder برای خانواده {$familyCode} به‌روزرسانی شد");
                    } else {
                        // Create new record if no placeholder exists
                        $insurance = FamilyInsurance::create([
                            'family_id' => $family->id,
                            'insurance_type' => $insuranceType,
                            'premium_amount' => $premiumAmount,
                            'start_date' => $startDate,
                            'end_date' => $endDate,
                            'status' => 'insured'
                        ]);
                        $wasRecentlyCreated = true;
                        Log::info("✅ ردیف {$i}: رکورد بیمه جدید برای خانواده {$familyCode} ایجاد شد");
                    }
                    
                    // Update family status
                    $oldStatus = $family->wizard_status;
                    $family->setAttribute('wizard_status', InsuranceWizardStep::INSURED->value);
                    $family->status = 'insured';
                    $family->is_insured = true;
                    $family->save();
                    
                    // Log status change
                    FamilyStatusLog::create([
                        'family_id' => $family->id,
                        'user_id' => Auth::id(),
                        'from_status' => $oldStatus,
                        'to_status' => InsuranceWizardStep::INSURED->value,
                        'comments' => "آپلود اکسل بیمه و انتقال به وضعیت بیمه شده",
                        'batch_id' => $batchId
                    ]);
                    
                    // Update counters
                    $familyCodes[] = $familyCode;
                    if ($wasRecentlyCreated) {
                        $created++;
                        $createdFamilyCodes[] = $familyCode;
                    } else {
                        $updated++;
                        $updatedFamilyCodes[] = $familyCode;
                    }
                    
                    if (!in_array($family->id, $familiesUpdated)) {
                        $familiesUpdated[] = $family->id;
                    }
                    
                    $totalInsuranceAmount += $premiumAmount;

                    // Log successful row
                    InsuranceImportLogger::logRow($importLog, [
                        'row_number' => $i + 1,
                        'family_code' => $familyCode,
                        'insurance_type' => $insuranceType,
                        'premium_amount' => $premiumAmount,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'family_id' => $family->id,
                    ], $wasRecentlyCreated ? 'created' : 'updated', 
                       $wasRecentlyCreated ? 'رکورد جدید ایجاد شد' : 'رکورد به‌روزرسانی شد');
                    
                } catch (\Exception $rowException) {
                    $errorMessage = "ردیف " . ($i + 1) . ": " . $rowException->getMessage();
                    $errors[] = $errorMessage;
                    $skipped++;
                    
                    Log::error("❌ " . $errorMessage);
                    
                    // Log error row
                    InsuranceImportLogger::logRow($importLog, [
                        'row_number' => $i + 1,
                        'family_code' => $familyCode ?? 'نامشخص',
                        'insurance_type' => $insuranceType ?? 'نامشخص',
                        'premium_amount' => isset($premiumAmount) ? $premiumAmount : 0,
                        'start_date' => isset($startDate) ? (is_string($startDate) ? $startDate : $startDate->format('Y-m-d')) : 'نامشخص',
                        'end_date' => isset($endDate) ? (is_string($endDate) ? $endDate : $endDate->format('Y-m-d')) : 'نامشخص',
                    ], 'error', $errorMessage);
                    
                    continue;
                }
            }
            
            DB::commit();
            Log::info("✅ تراکنش با موفقیت انجام شد: {$created} ایجاد، {$updated} به‌روزرسانی، {$skipped} خطا");
            
            // Complete import log
            InsuranceImportLogger::completeLog($importLog, [
                'created_count' => $created,
                'updated_count' => $updated,
                'skipped_count' => $skipped,
                'error_count' => count($errors),
                'total_insurance_amount' => $totalInsuranceAmount,
                'family_codes' => json_encode(array_unique($familyCodes)),
                'updated_family_codes' => json_encode(array_unique($updatedFamilyCodes)),
                'created_family_codes' => json_encode(array_unique($createdFamilyCodes)),
                'errors' => !empty($errors) ? json_encode($errors) : null,
                'message' => "ایمپورت با موفقیت انجام شد. ایجاد: {$created}, به‌روزرسانی: {$updated}, خطا: {$skipped}"
            ]);
            
            return [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'total_insurance_amount' => $totalInsuranceAmount,
                'families_updated' => $familiesUpdated,
                'message' => "ایمپورت با موفقیت انجام شد. ایجاد: {$created}, به‌روزرسانی: {$updated}, خطا: {$skipped}"
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ خطا در پردازش اطلاعات اکسل: ' . $e->getMessage());
            
            if ($importLog) {
                InsuranceImportLogger::logError($importLog, $e);
            }
            
            throw $e;
        }
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
            
            Log::info("📊 سهم {$share->share_type} به‌روزرسانی شد: {$share->percentage}% = {$amount} تومان");
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
}