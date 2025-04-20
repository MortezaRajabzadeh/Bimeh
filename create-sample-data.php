<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Region;
use App\Models\Organization;
use App\Models\User;
use App\Models\Family;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

try {
    // بررسی وجود منطقه
    if (Region::count() == 0) {
        echo "ایجاد منطقه‌های نمونه...\n";
        
        // منطقه‌های تهران
        $regions = [
            [
                'name' => 'منطقه ۱ تهران',
                'province' => 'تهران',
                'city' => 'تهران',
                'is_active' => true
            ],
            [
                'name' => 'منطقه ۲ تهران',
                'province' => 'تهران',
                'city' => 'تهران',
                'is_active' => true
            ],
            [
                'name' => 'منطقه ۳ تهران',
                'province' => 'تهران',
                'city' => 'تهران',
                'is_active' => true
            ],
            [
                'name' => 'منطقه ۴ تهران',
                'province' => 'تهران',
                'city' => 'تهران',
                'is_active' => true
            ],
            [
                'name' => 'منطقه ۵ تهران',
                'province' => 'تهران',
                'city' => 'تهران',
                'is_active' => true
            ],
        ];
        
        foreach ($regions as $region) {
            Region::create($region);
        }
        
        echo "تعداد " . Region::count() . " منطقه ایجاد شد.\n";
    } else {
        echo "منطقه‌ها از قبل وجود دارند.\n";
    }
    
    // بررسی وجود سازمان‌ها
    if (Organization::count() == 0) {
        echo "ایجاد سازمان‌های نمونه...\n";
        
        // ایجاد خیریه نمونه
        $charity = Organization::create([
            'name' => 'خیریه نمونه',
            'type' => 'charity',
            'code' => 'CH001',
            'phone' => '021-12345678',
            'email' => 'charity@example.com',
            'address' => 'تهران، خیابان انقلاب',
            'is_active' => true,
        ]);
        
        // ایجاد بیمه نمونه
        $insurance = Organization::create([
            'name' => 'بیمه نمونه',
            'type' => 'insurance',
            'code' => 'IN001',
            'phone' => '021-87654321',
            'email' => 'insurance@example.com',
            'address' => 'تهران، خیابان آزادی',
            'is_active' => true,
        ]);
        
        echo "تعداد " . Organization::count() . " سازمان ایجاد شد.\n";
    } else {
        $charity = Organization::where('type', 'charity')->first();
        $insurance = Organization::where('type', 'insurance')->first();
        echo "سازمان‌ها از قبل وجود دارند.\n";
    }
    
    // بررسی وجود کاربران
    if (User::count() < 3) {
        echo "ایجاد کاربران نمونه...\n";
        
        // ایجاد کاربر ادمین
        if (!User::where('user_type', 'admin')->exists()) {
            User::create([
                'username' => 'admin',
                'name' => 'مدیر سیستم',
                'email' => 'admin@example.com',
                'mobile' => '09121234567',
                'password' => Hash::make('password'),
                'user_type' => 'admin',
                'is_active' => true,
            ]);
        }
        
        // ایجاد کاربر خیریه
        if (!User::where('user_type', 'charity')->exists()) {
            User::create([
                'username' => 'charity',
                'name' => 'کاربر خیریه',
                'email' => 'charity_user@example.com',
                'mobile' => '09123456789',
                'password' => Hash::make('password'),
                'user_type' => 'charity',
                'organization_id' => $charity->id,
                'is_active' => true,
            ]);
        }
        
        // ایجاد کاربر بیمه
        if (!User::where('user_type', 'insurance')->exists()) {
            User::create([
                'username' => 'insurance',
                'name' => 'کاربر بیمه',
                'email' => 'insurance_user@example.com',
                'mobile' => '09198765432',
                'password' => Hash::make('password'),
                'user_type' => 'insurance',
                'organization_id' => $insurance->id,
                'is_active' => true,
            ]);
        }
        
        echo "کاربران نمونه ایجاد شدند.\n";
    } else {
        echo "کاربران از قبل وجود دارند.\n";
    }
    
    // ایجاد خانواده‌ها و اعضای آن‌ها
    if (Family::count() == 0) {
        echo "ایجاد خانواده‌های نمونه...\n";
        
        // لیست نام خانوادگی‌ها
        $familyNames = ['محمدی', 'احمدی', 'رضایی', 'موسوی', 'حسینی', 'کریمی', 'صادقی', 'نجفی', 'عباسی', 'علوی'];
        
        // لیست نام‌های پسرانه
        $maleNames = ['علی', 'محمد', 'حسین', 'رضا', 'مهدی', 'امیر', 'سعید', 'امین', 'حسن', 'جواد'];
        
        // لیست نام‌های دخترانه
        $femaleNames = ['فاطمه', 'زهرا', 'مریم', 'لیلا', 'سارا', 'نرگس', 'زینب', 'معصومه', 'ریحانه', 'مائده'];
        
        // لیست نام پدر
        $fatherNames = ['عباس', 'محمود', 'احمد', 'اکبر', 'اصغر', 'حسن', 'حسین', 'مهدی', 'رضا', 'محمد'];
        
        // لیست تحصیلات
        $educations = ['بی‌سواد', 'ابتدایی', 'سیکل', 'دیپلم', 'کارشناسی', 'کارشناسی ارشد', 'دکتری'];
        
        // لیست شغل‌ها
        $occupations = ['کارگر', 'کارمند', 'آزاد', 'بازنشسته', 'بیکار', 'خانه‌دار', 'دانشجو', 'دانش‌آموز', 'سایر'];
        
        // لیست وضعیت مسکن
        $housingStatus = ['owner', 'tenant', 'relative', 'other'];
        
        $regions = Region::all();
        $charityId = Organization::where('type', 'charity')->first()->id;
        $insuranceId = Organization::where('type', 'insurance')->first()->id;
        $registeredBy = User::where('user_type', 'charity')->first()->id;
        
        // ایجاد ۱۰ خانواده
        for ($i = 0; $i < 10; $i++) {
            $familyName = $familyNames[array_rand($familyNames)];
            $region = $regions->random();
            $isInsured = rand(0, 1) === 1;
            
            // ایجاد خانواده
            $family = new Family();
            $family->charity_id = $charityId;
            $family->insurance_id = $isInsured ? $insuranceId : null;
            $family->region_id = $region->id;
            $family->registered_by = $registeredBy;
            $family->family_code = 'F-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
            $family->address = 'آدرس تست در ' . $region->city . '، ' . $region->name . '، خیابان ' . ($i + 1);
            $family->postal_code = '1' . rand(1000000, 9999999);
            $family->housing_status = $housingStatus[array_rand($housingStatus)];
            $family->is_insured = $isInsured;
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
                $member->gender = $gender === 'male' ? 'مرد' : 'زن';
                
                // در ابتدا هیچ کس سرپرست نیست
                $member->is_head = false;
                
                if ($j === 0) {
                    // اولین عضو همیشه سرپرست است (برای مطمئن شدن از اینکه هر خانواده یک سرپرست دارد)
                    $member->relationship = 'سرپرست';
                    $member->is_head = true;
                    
                    // سرپرست معمولاً بالای 18 سال است
                    $member->birth_date = now()->subYears(rand(18, 80))->format('Y-m-d');
                } else {
                    $member->relationship = ['همسر', 'فرزند', 'پدر', 'مادر', 'خواهر', 'برادر'][rand(0, 5)];
                }
                
                $member->marital_status = rand(0, 1) === 1 ? 'متاهل' : 'مجرد';
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
        
        echo "تعداد " . Family::count() . " خانواده و " . Member::count() . " عضو با موفقیت ایجاد شد.\n";
    } else {
        echo "خانواده‌ها از قبل وجود دارند.\n";
    }
    
    echo "همه داده‌های نمونه با موفقیت ایجاد شدند.\n";
    
} catch (Exception $e) {
    echo "خطا: " . $e->getMessage() . "\n";
    echo "در فایل: " . $e->getFile() . " خط " . $e->getLine() . "\n";
} 