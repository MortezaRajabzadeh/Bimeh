<?php

namespace Database\Seeders;

use App\Models\Family;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FamilySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // پاک کردن داده‌های قبلی با حفظ Foreign Keys
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Member::query()->delete();
        Family::query()->delete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        // ایجاد یک منطقه نمونه
        $region = Region::firstOrCreate(
            ['name' => 'منطقه ۱'],
            [
                'province' => 'تهران',
                'city' => 'تهران',
                'description' => 'منطقه یک تهران',
                'is_active' => true,
            ]
        );

        // دریافت سازمان خیریه و بیمه
        $charity = Organization::where('type', 'charity')->first();
        $insurance = Organization::where('type', 'insurance')->first();
        $charityUser = User::where('user_type', 'charity')->first();

        if (!$charity || !$charityUser) {
            return;
        }

        // ایجاد خانواده‌های بیمه شده
        for ($i = 1; $i <= 50; $i++) {
            $family = Family::create([
                'family_code' => 'FAM-INS-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'region_id' => $region->id,
                'charity_id' => $charity->id,
                'insurance_id' => $insurance ? $insurance->id : null,
                'registered_by' => $charityUser->id,
                'address' => 'تهران، خیابان شماره ' . $i,
                'postal_code' => '1' . str_pad($i, 9, '0', STR_PAD_LEFT),
                'housing_status' => collect(['owner', 'tenant', 'relative', 'other'])->random(),
                'status' => 'approved',
                'poverty_confirmed' => true,
                'is_insured' => true,
                'verified_at' => now(),
            ]);

            // ایجاد سرپرست خانوار
            $head = Member::create([
                'family_id' => $family->id,
                'first_name' => 'سرپرست',
                'last_name' => 'خانواده ' . $i,
                'national_code' => '00' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'father_name' => 'پدر سرپرست',
                'birth_date' => now()->subYears(40 + $i % 10),
                'gender' => 'male',
                'marital_status' => 'married',
                'relationship' => 'head',
                'is_head' => true,
                'has_insurance' => true,
                'mobile' => '0912' . str_pad($i, 7, '0', STR_PAD_LEFT),
            ]);

            // ایجاد همسر
            Member::create([
                'family_id' => $family->id,
                'first_name' => 'همسر',
                'last_name' => 'خانواده ' . $i,
                'national_code' => '00' . str_pad($i + 100, 8, '0', STR_PAD_LEFT),
                'father_name' => 'پدر همسر',
                'birth_date' => now()->subYears(35 + $i % 10),
                'gender' => 'female',
                'marital_status' => 'married',
                'relationship' => 'spouse',
                'is_head' => false,
                'has_insurance' => true,
            ]);

            // ایجاد فرزندان
            $childCount = rand(1, 3);
            for ($j = 1; $j <= $childCount; $j++) {
                Member::create([
                    'family_id' => $family->id,
                    'first_name' => 'فرزند ' . $j,
                    'last_name' => 'خانواده ' . $i,
                    'national_code' => '00' . str_pad($i * 1000 + $j, 8, '0', STR_PAD_LEFT),
                    'father_name' => $head->first_name,
                    'birth_date' => now()->subYears(10 + $j * 3),
                    'gender' => $j % 2 ? 'male' : 'female',
                    'marital_status' => 'single',
                    'relationship' => 'child',
                    'is_head' => false,
                    'has_insurance' => true,
                ]);
            }
        }

        // ایجاد خانواده‌های بدون بیمه
        for ($i = 1; $i <= 30; $i++) {
            $family = Family::create([
                'family_code' => 'FAM-NINS-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'region_id' => $region->id,
                'charity_id' => $charity->id,
                'registered_by' => $charityUser->id,
                'address' => 'تهران، خیابان جنوبی شماره ' . $i,
                'postal_code' => '2' . str_pad($i, 9, '0', STR_PAD_LEFT),
                'housing_status' => collect(['tenant', 'relative', 'other'])->random(),
                'status' => 'pending',
                'poverty_confirmed' => true,
                'is_insured' => false,
            ]);

            // ایجاد سرپرست خانوار
            $head = Member::create([
                'family_id' => $family->id,
                'first_name' => 'سرپرست',
                'last_name' => 'بدون بیمه ' . $i,
                'national_code' => '11' . str_pad($i, 8, '0', STR_PAD_LEFT),
                'father_name' => 'پدر سرپرست',
                'birth_date' => now()->subYears(45 + $i % 15),
                'gender' => 'male',
                'marital_status' => 'married',
                'relationship' => 'head',
                'is_head' => true,
                'has_insurance' => false,
                'mobile' => '0913' . str_pad($i, 7, '0', STR_PAD_LEFT),
            ]);

            // ایجاد همسر
            Member::create([
                'family_id' => $family->id,
                'first_name' => 'همسر',
                'last_name' => 'بدون بیمه ' . $i,
                'national_code' => '11' . str_pad($i + 100, 8, '0', STR_PAD_LEFT),
                'father_name' => 'پدر همسر',
                'birth_date' => now()->subYears(40 + $i % 15),
                'gender' => 'female',
                'marital_status' => 'married',
                'relationship' => 'spouse',
                'is_head' => false,
                'has_insurance' => false,
            ]);

            // ایجاد فرزندان
            $childCount = rand(2, 5);
            for ($j = 1; $j <= $childCount; $j++) {
                Member::create([
                    'family_id' => $family->id,
                    'first_name' => 'فرزند ' . $j,
                    'last_name' => 'بدون بیمه ' . $i,
                    'national_code' => '11' . str_pad($i * 1000 + $j, 8, '0', STR_PAD_LEFT),
                    'father_name' => $head->first_name,
                    'birth_date' => now()->subYears(5 + $j * 2),
                    'gender' => $j % 2 ? 'male' : 'female',
                    'marital_status' => 'single',
                    'relationship' => 'child',
                    'is_head' => false,
                    'has_insurance' => false,
                ]);
            }
        }
    }
} 