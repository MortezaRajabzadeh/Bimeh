<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Region;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateFamiliesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // اطمینان از وجود منطقه، خیریه و بیمه
        $region = Region::first();
        if (!$region) {
            $region = Region::create([
                'name' => 'تهران',
                'province' => 'تهران',
                'city' => 'تهران',
                'is_active' => true
            ]);
        }

        $charity = Organization::where('type', 'charity')->first();
        if (!$charity) {
            $charity = Organization::create([
                'name' => 'خیریه نمونه',
                'type' => 'charity',
                'is_active' => true
            ]);
        }

        $insurance = Organization::where('type', 'insurance')->first();
        if (!$insurance) {
            $insurance = Organization::create([
                'name' => 'شرکت بیمه نمونه',
                'type' => 'insurance',
                'is_active' => true
            ]);
        }

        // لیست نام خانوادگی‌ها
        $familyNames = ['محمدی', 'احمدی', 'رضایی', 'موسوی', 'حسینی', 'کریمی', 'صادقی', 'نجفی', 'عباسی', 'علوی'];
        
        // لیست نام‌های پسرانه
        $maleNames = ['علی', 'محمد', 'حسین', 'رضا', 'مهدی', 'امیر', 'سعید', 'امین', 'حسن', 'جواد'];
        
        // لیست نام‌های دخترانه
        $femaleNames = ['فاطمه', 'زهرا', 'مریم', 'لیلا', 'سارا', 'نرگس', 'زینب', 'معصومه', 'ریحانه', 'مائده'];
        
        // لیست نام پدر
        $fatherNames = ['عباس', 'محمود', 'احمد', 'اکبر', 'اصغر', 'حسن', 'حسین', 'مهدی', 'رضا', 'محمد'];
        
        // لیست تحصیلات
        $educations = ['illiterate', 'primary', 'secondary', 'diploma', 'bachelor', 'master', 'doctorate'];
        
        // لیست شغل‌ها
        $occupations = ['کارگر', 'کارمند', 'آزاد', 'بازنشسته', 'بیکار', 'خانه‌دار', 'دانشجو', 'دانش‌آموز', 'سایر'];
        
        // لیست وضعیت مسکن
        $housingStatus = ['owner', 'tenant', 'relative', 'other'];

        // ایجاد 5 خانواده
        for ($i = 0; $i < 5; $i++) {
            $familyName = $familyNames[array_rand($familyNames)];
            
            // ایجاد خانواده
            $family = new Family();
            $family->charity_id = $charity->id;
            $family->insurance_id = $insurance->id;
            $family->region = 'تهران';
            $family->family_code = 'F-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
            $family->address = 'آدرس تست در تهران، خیابان ' . ($i + 1);
            $family->postal_code = '1' . rand(1000000, 9999999);
            $family->housing_status = $housingStatus[array_rand($housingStatus)];
            $family->is_insured = rand(0, 1) === 1;
            $family->status = ['pending', 'approved', 'rejected'][rand(0, 2)];
            $family->poverty_confirmed = rand(0, 1) === 1;
            
            // اگر تایید شده است تاریخ تایید را تنظیم کنیم
            if ($family->status === 'approved') {
                $family->verified_at = now()->subDays(rand(1, 30));
            }
            
            // اگر رد شده است دلیل رد را تنظیم کنیم
            if ($family->status === 'rejected') {
                $family->rejection_reason = 'دلیل تست برای رد درخواست';
            }
            
            $family->save();
            
            // تعداد اعضای خانواده (2 تا 5 نفر)
            $memberCount = rand(2, 5);
            
            // ایجاد اعضای خانواده
            for ($j = 0; $j < $memberCount; $j++) {
                $gender = rand(0, 1) === 1 ? 'male' : 'female';
                $firstName = $gender === 'male' ? $maleNames[array_rand($maleNames)] : $femaleNames[array_rand($femaleNames)];
                
                $member = new Member();
                $member->family_id = $family->id;
                $member->first_name = $firstName;
                $member->last_name = $familyName;
                $member->father_name = $fatherNames[array_rand($fatherNames)];
                $member->national_code = '00' . rand(10000000, 99999999);
                $member->birth_date = now()->subYears(rand(1, 80))->format('Y-m-d');
                $member->gender = $gender;
                
                // در ابتدا هیچ کس سرپرست نیست
                $member->is_head = false;
                
                if ($j === 0) {
                    // اولین عضو همیشه سرپرست است (برای مطمئن شدن از اینکه هر خانواده یک سرپرست دارد)
                    $member->relationship = 'head';
                    $member->is_head = true;
                    
                    // سرپرست معمولاً بالای 18 سال است
                    $member->birth_date = now()->subYears(rand(18, 80))->format('Y-m-d');
                } else {
                    $member->relationship = ['spouse', 'child', 'parent', 'other'][rand(0, 3)];
                }
                
                $member->marital_status = ['single', 'married', 'divorced', 'widowed'][rand(0, 3)];
                $member->education = $educations[array_rand($educations)];
                $member->occupation = $occupations[array_rand($occupations)];
                $member->is_employed = $member->occupation !== 'بیکار' && $member->occupation !== 'خانه‌دار';
                $member->has_disability = rand(0, 10) < 2; // 20% احتمال معلولیت
                $member->has_chronic_disease = rand(0, 10) < 3; // 30% احتمال بیماری مزمن
                $member->has_insurance = $family->is_insured;
                
                if ($member->has_insurance) {
                    $member->insurance_type = ['تامین اجتماعی', 'خدمات درمانی', 'سلامت', 'نیروهای مسلح', 'درمان تکمیلی'][rand(0, 4)];
                }
                
                $member->mobile = '09' . rand(100000000, 999999999);
                $member->save();
            }
        }
    }
} 