<?php

namespace Database\Seeders;

use App\Models\FundingSource;
use App\Models\Benefactor;
use Illuminate\Database\Seeder;

class FamilyFundingAllocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ایجاد نمونه نیکوکاران
        $benefactors = [
            [
                'name' => 'آقای علی احمدی',
                'phone' => '09121234567',
                'email' => 'ali.ahmadi@email.com',
                'total_contributed' => 50000000,
                'is_active' => true,
                'notes' => 'نیکوکار فعال در حوزه بیمه درمان',
            ],
            [
                'name' => 'خانم فاطمه کریمی',
                'phone' => '09127654321',
                'email' => 'f.karimi@email.com',
                'total_contributed' => 30000000,
                'is_active' => true,
                'notes' => 'حمایت از خانواده‌های نیازمند',
            ],
            [
                'name' => 'موسسه خیریه امید',
                'phone' => '02112345678',
                'email' => 'info@omid.org',
                'total_contributed' => 100000000,
                'is_active' => true,
                'notes' => 'موسسه خیریه مطرح در زمینه بیمه',
            ],
        ];

        foreach ($benefactors as $benefactor) {
            Benefactor::create($benefactor);
        }

        // ایجاد منابع مالی نمونه
        $fundingSources = [
            [
                'name' => 'بودجه CSR شرکت پتروشیمی',
                'type' => 'corporate',
                'source_type' => FundingSource::TYPE_CSR,
                'description' => 'بودجه مسئولیت اجتماعی شرکت پتروشیمی برای بیمه خانواده‌های کارگری',
                'annual_budget' => 2000000000, // 2 میلیارد تومان
                'allocated_amount' => 500000000,
                'remaining_amount' => 1500000000,
                'is_active' => true,
                'contact_info' => 'تلفن: 021-12345678 - واحد CSR',
            ],
            [
                'name' => 'بانک ملی - بودجه اجتماعی',
                'type' => 'bank',
                'source_type' => FundingSource::TYPE_BANK,
                'description' => 'بودجه تخصیصی بانک ملی برای حمایت از خانواده‌های نیازمند',
                'annual_budget' => 1500000000, // 1.5 میلیارد تومان
                'allocated_amount' => 300000000,
                'remaining_amount' => 1200000000,
                'is_active' => true,
                'contact_info' => 'تلفن: 021-98765432 - واحد خدمات اجتماعی',
            ],
            [
                'name' => 'اعتبارات دولتی',
                'type' => 'government',
                'source_type' => FundingSource::TYPE_GOVERNMENT,
                'description' => 'بودجه تخصیصی دولت برای بیمه اقشار آسیب‌پذیر',
                'annual_budget' => 5000000000, // 5 میلیارد تومان
                'allocated_amount' => 1000000000,
                'remaining_amount' => 4000000000,
                'is_active' => true,
                'contact_info' => 'تلفن: 021-55667788 - وزارت کار',
            ],
            [
                'name' => 'کمک‌های نیکوکاران',
                'type' => 'benefactor',
                'source_type' => FundingSource::TYPE_BENEFACTOR,
                'description' => 'کمک‌های جمع‌آوری شده از نیکوکاران برای بیمه خانواده‌ها',
                'annual_budget' => 800000000, // 800 میلیون تومان
                'allocated_amount' => 200000000,
                'remaining_amount' => 600000000,
                'benefactor_id' => 1, // اولین نیکوکار
                'is_active' => true,
                'contact_info' => 'از طریق نیکوکاران',
            ],
        ];

        foreach ($fundingSources as $source) {
            FundingSource::create($source);
        }

        $this->command->info('✅ منابع مالی و نیکوکاران نمونه ایجاد شدند.');
        $this->command->info('📊 آمار:');
        $this->command->info('   - تعداد نیکوکاران: ' . Benefactor::count());
        $this->command->info('   - تعداد منابع مالی: ' . FundingSource::count());
        $this->command->info('   - مجموع بودجه: ' . number_format(FundingSource::sum('annual_budget')) . ' تومان');
        $this->command->info('   - بودجه باقیمانده: ' . number_format(FundingSource::sum('remaining_amount')) . ' تومان');
    }
}
