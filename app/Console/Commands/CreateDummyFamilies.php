<?php

namespace App\Console\Commands;

use App\Models\Family;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Region;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateDummyFamilies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-dummy-families {count=10 : تعداد خانواده‌هایی که باید ایجاد شوند} {--force : حذف خانواده‌های موجود و ایجاد داده‌های جدید}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'ایجاد خانواده‌های نمونه برای تست سیستم';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('شروع ایجاد خانواده‌های نمونه...');

        // بررسی وجود خانواده‌های قبلی
        $familyCount = Family::count();
        if ($familyCount > 0) {
            if ($this->option('force')) {
                if (!$this->confirm("تمام خانواده‌های موجود ({$familyCount} خانواده) حذف خواهند شد. آیا مطمئن هستید؟", false)) {
                    $this->warn('عملیات لغو شد.');
                    return 1;
                }
                
                $this->warn('حذف تمام خانواده‌ها و اعضای آن‌ها...');
                try {
                    // غیرفعال کردن محدودیت کلید خارجی برای حذف
                    Schema::disableForeignKeyConstraints();
                    
                    // حذف داده‌ها
                    $this->info('حذف اعضا...');
                    Member::query()->delete();
                    $this->info('حذف خانواده‌ها...');
                    Family::query()->delete();
                    
                    // فعال‌سازی مجدد محدودیت کلید خارجی
                    Schema::enableForeignKeyConstraints();
                    
                    $this->info('داده‌های قبلی با موفقیت حذف شدند.');
                } catch (\Exception $e) {
                    // فعال‌سازی مجدد محدودیت کلید خارجی در صورت خطا
                    Schema::enableForeignKeyConstraints();
                    $this->error('خطا در حذف داده‌ها: ' . $e->getMessage());
                    return 1;
                }
            } else {
                $this->info("در حال حاضر {$familyCount} خانواده در سیستم وجود دارد.");
                if (!$this->confirm('آیا می‌خواهید خانواده‌های بیشتری اضافه کنید؟', true)) {
                    $this->warn('عملیات لغو شد.');
                    return 1;
                }
            }
        }

        // بررسی وجود منطقه و خیریه
        $region = Region::first();
        if (!$region) {
            $region = new Region();
            $region->name = 'تهران';
            $region->province = 'تهران';
            $region->is_active = true;
            $region->save();
            $this->info('منطقه پیش‌فرض ایجاد شد.');
        }

        // بررسی وجود حداقل یک خیریه
        $charity = Organization::where('type', 'charity')->first();
        if (!$charity) {
            $charity = new Organization();
            $charity->name = 'خیریه نمونه';
            $charity->type = 'charity';
            $charity->is_active = true;
            $charity->save();
            $this->info('خیریه پیش‌فرض ایجاد شد.');
        }

        // بررسی وجود حداقل یک شرکت بیمه
        $insurance = Organization::where('type', 'insurance')->first();
        if (!$insurance) {
            $insurance = new Organization();
            $insurance->name = 'شرکت بیمه نمونه';
            $insurance->type = 'insurance';
            $insurance->is_active = true;
            $insurance->save();
            $this->info('شرکت بیمه پیش‌فرض ایجاد شد.');
        }

        // بررسی وجود کاربر با نقش خیریه
        $charityUser = User::whereHas('roles', function($query) {
            $query->where('name', 'charity');
        })->first();
        
        if (!$charityUser) {
            $this->warn('کاربر با نقش خیریه یافت نشد. استفاده از کاربر پشتیبان...');
            
            // استفاده از شناسه کاربر اول به عنوان پشتیبان
            $adminUser = User::first();
            if ($adminUser) {
                $charityUser = $adminUser;
                $this->info('از کاربر شناسه ' . $adminUser->id . ' به عنوان کاربر پشتیبان استفاده می‌شود.');
            } else {
                $this->error('هیچ کاربری در سیستم یافت نشد!');
                return 1;
            }
        }
        
        // تنظیم organization_id کاربر خیریه
        if (!$charityUser->organization_id) {
            $charityUser->organization_id = $charity->id;
            $charityUser->save();
            $this->info('شناسه سازمان برای کاربر خیریه تنظیم شد.');
        }

        $count = (int)$this->argument('count');
        $this->info("ایجاد {$count} خانواده نمونه...");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();
        
        $cities = ['تهران', 'شیراز', 'اصفهان', 'مشهد', 'تبریز', 'کرج', 'قم', 'یزد', 'کرمان', 'رشت'];
        $familyNames = ['محمدی', 'احمدی', 'رضایی', 'موسوی', 'حسینی', 'کریمی', 'صادقی', 'نجفی', 'عباسی', 'علوی'];
        $firstNames = ['علی', 'محمد', 'حسین', 'رضا', 'مهدی', 'امیر', 'سعید', 'امین', 'حسن', 'جواد', 'فاطمه', 'زهرا', 'مریم', 'لیلا', 'سارا'];
        $fatherNames = ['عباس', 'محمود', 'احمد', 'اکبر', 'اصغر', 'حسن', 'حسین', 'مهدی', 'رضا', 'محمد'];
        $education = ['بی‌سواد', 'ابتدایی', 'سیکل', 'دیپلم', 'کارشناسی', 'کارشناسی ارشد', 'دکتری'];
        $occupations = ['کارگر', 'کارمند', 'آزاد', 'بازنشسته', 'بیکار', 'خانه‌دار', 'دانشجو', 'دانش‌آموز', 'سایر'];
        $housingStatus = ['رهن', 'اجاره', 'ملکی', 'سازمانی'];
        
        try {
            for ($i = 0; $i < $count; $i++) {
                DB::beginTransaction();
                
                try {
                    // ایجاد خانواده
                    $family = new Family();
                    $family->charity_id = $charity->id;
                    $family->insurance_id = $insurance->id;
                    $family->region_id = $region->id;
                    $family->family_code = 'F-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT);
                    $family->address = 'آدرس تست در ' . $cities[array_rand($cities)];
                    $family->postal_code = '1' . rand(1000000, 9999999);
                    $family->housing_status = $housingStatus[array_rand($housingStatus)];
                    $family->housing_description = 'توضیحات تست برای وضعیت مسکن';
                    $family->is_insured = rand(0, 1) === 1;
                    $family->status = ['در انتظار', 'تایید شده', 'رد شده'][rand(0, 2)];
                    if ($family->status === 'رد شده') {
                        $family->rejection_reason = 'دلیل تست برای رد درخواست';
                    }
                    $family->registered_by = $charityUser->id;
                    $family->save();

                    // تعداد تصادفی اعضای خانواده (1 تا 6 نفر)
                    $memberCount = rand(1, 6);
                    $familyName = $familyNames[array_rand($familyNames)];
                    
                    // ایجاد سرپرست خانواده
                    $head = new Member();
                    $head->family_id = $family->id;
                    $head->first_name = $firstNames[array_rand($firstNames)];
                    $head->last_name = $familyName;
                    $head->father_name = $fatherNames[array_rand($fatherNames)];
                    $head->national_code = '00' . rand(10000000, 99999999);
                    $head->birth_date = now()->subYears(rand(25, 60))->format('Y-m-d');
                    $head->gender = rand(0, 1) === 1 ? 'مرد' : 'زن';
                    $head->is_head = true;
                    $head->relationship = 'سرپرست';
                    $head->marital_status = rand(0, 1) === 1 ? 'متاهل' : 'مجرد';
                    $head->education = $education[array_rand($education)];
                    $head->occupation = $occupations[array_rand($occupations)];
                    $head->is_employed = rand(0, 1) === 1;
                    $head->has_disability = rand(0, 10) < 2; // 20% احتمال معلولیت
                    $head->has_chronic_disease = rand(0, 10) < 3; // 30% احتمال بیماری مزمن
                    $head->has_insurance = rand(0, 1) === 1;
                    if ($head->has_insurance) {
                        $head->insurance_type = ['تامین اجتماعی', 'خدمات درمانی', 'سلامت', 'نیروهای مسلح', 'سایر'][rand(0, 4)];
                    }
                    $head->mobile = '09' . rand(100000000, 999999999);
                    $head->phone = '0' . rand(1000000000, 9999999999);
                    $head->save();

                    // ایجاد سایر اعضای خانواده
                    for ($j = 1; $j < $memberCount; $j++) {
                        $member = new Member();
                        $member->family_id = $family->id;
                        $member->first_name = $firstNames[array_rand($firstNames)];
                        $member->last_name = $familyName;
                        $member->father_name = $fatherNames[array_rand($fatherNames)];
                        $member->national_code = '00' . rand(10000000, 99999999);
                        $member->birth_date = now()->subYears(rand(1, 80))->format('Y-m-d');
                        $member->gender = rand(0, 1) === 1 ? 'مرد' : 'زن';
                        $member->is_head = false;
                        $member->relationship = ['همسر', 'فرزند', 'پدر', 'مادر', 'خواهر', 'برادر'][rand(0, 5)];
                        $member->marital_status = rand(0, 1) === 1 ? 'متاهل' : 'مجرد';
                        $member->education = $education[array_rand($education)];
                        $member->occupation = $occupations[array_rand($occupations)];
                        $member->is_employed = rand(0, 1) === 1;
                        $member->has_disability = rand(0, 10) < 2; // 20% احتمال معلولیت
                        $member->has_chronic_disease = rand(0, 10) < 3; // 30% احتمال بیماری مزمن
                        $member->has_insurance = rand(0, 1) === 1;
                        if ($member->has_insurance) {
                            $member->insurance_type = ['تامین اجتماعی', 'خدمات درمانی', 'سلامت', 'نیروهای مسلح', 'سایر'][rand(0, 4)];
                        }
                        $member->mobile = rand(0, 1) === 1 ? '09' . rand(100000000, 999999999) : null;
                        $member->save();
                    }
                    
                    DB::commit();
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error('خطا در ایجاد خانواده شماره ' . ($i + 1) . ': ' . $e->getMessage());
                }

                $progressBar->advance();
            }
            
            $progressBar->finish();
            $this->newLine();
            $this->info("تعداد {$count} خانواده نمونه با موفقیت ایجاد شدند.");
            $this->info("تعداد کل خانواده‌ها: " . Family::count());
            $this->info("تعداد کل اعضا: " . Member::count());
            
            return 0;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('خطا در ایجاد داده‌های نمونه: ' . $e->getMessage());
            return 1;
        }
    }
}
