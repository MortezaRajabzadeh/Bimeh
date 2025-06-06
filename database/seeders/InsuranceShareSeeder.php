<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FamilyInsurance;
use App\Models\InsuranceShare;
use App\Models\FundingSource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InsuranceShareSeeder extends Seeder
{
    /**
     * اجرای سیدر
     */
    public function run()
    {
        $this->command->info('شروع سیدر سهام بیمه برای خانواده‌ها...');
        
        // دریافت منابع مالی فعال
        $fundingSources = FundingSource::where('is_active', true)->get();
        
        if ($fundingSources->isEmpty()) {
            $this->command->error('هیچ منبع مالی فعالی یافت نشد. لطفاً ابتدا FundingSourceSeeder را اجرا کنید.');
            return;
        }

        // نگاشت بین type در funding_sources و payer_type در insurance_shares
        $typeMapping = [
            'bank' => 'bank',
            'charity' => 'charity',
            'person' => 'individual_donor',
            'government' => 'government',
            'other' => 'other'
        ];
        
        // دریافت همه بیمه‌های خانواده‌ها
        $familyInsurances = FamilyInsurance::all();
        
        $this->command->info("تعداد کل بیمه‌های خانواده‌ها: {$familyInsurances->count()}");
        
        // تعداد بیمه‌هایی که سهام دارند
        $insurancesWithShares = 0;
        
        // تعداد بیمه‌هایی که سهام جدید اضافه شد
        $insurancesWithNewShares = 0;
        
        DB::beginTransaction();
        
        try {
            // بررسی هر بیمه
            foreach ($familyInsurances as $insurance) {
                // بررسی اینکه آیا بیمه سهام دارد
                $hasShares = InsuranceShare::where('family_insurance_id', $insurance->id)->exists();
                
                if ($hasShares) {
                    $insurancesWithShares++;
                    continue;
                }
                
                // انتخاب دو منبع مالی تصادفی
                $selectedSources = $fundingSources->random(min(2, $fundingSources->count()));
                
                // مقادیر پیش‌فرض سهام
                $shares = [
                    [
                        'percentage' => 60,
                        'payer_type' => $typeMapping[$selectedSources[0]->type] ?? 'other',
                        'payer_name' => $selectedSources[0]->name,
                        'amount' => $insurance->premium_amount * 0.6,
                        'description' => 'سهم اصلی ایجاد شده توسط سیدر'
                    ]
                ];
                
                // اضافه کردن سهم دوم اگر وجود داشته باشد
                if (count($selectedSources) > 1) {
                    $shares[] = [
                        'percentage' => 40,
                        'payer_type' => $typeMapping[$selectedSources[1]->type] ?? 'other',
                        'payer_name' => $selectedSources[1]->name,
                        'amount' => $insurance->premium_amount * 0.4,
                        'description' => 'سهم ثانویه ایجاد شده توسط سیدر'
                    ];
                } else {
                    // اگر فقط یک منبع مالی وجود داشت، 100٪ به آن اختصاص بده
                    $shares[0]['percentage'] = 100;
                    $shares[0]['amount'] = $insurance->premium_amount;
                }
                
                // ایجاد سهام برای این بیمه
                foreach ($shares as $shareData) {
                    InsuranceShare::create([
                        'family_insurance_id' => $insurance->id,
                        'percentage' => $shareData['percentage'],
                        'payer_type' => $shareData['payer_type'],
                        'payer_name' => $shareData['payer_name'],
                        'amount' => $shareData['amount'],
                        'description' => $shareData['description'],
                        'is_paid' => false
                    ]);
                }
                
                $insurancesWithNewShares++;
            }
            
            DB::commit();
            
            $this->command->info("عملیات با موفقیت انجام شد:");
            $this->command->info(" - تعداد بیمه‌هایی که از قبل سهام داشتند: {$insurancesWithShares}");
            $this->command->info(" - تعداد بیمه‌هایی که سهام جدید اضافه شد: {$insurancesWithNewShares}");
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->command->error("خطا در ایجاد سهام بیمه: " . $e->getMessage());
            
            Log::error("خطا در سیدر InsuranceShareSeeder: " . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }
} 