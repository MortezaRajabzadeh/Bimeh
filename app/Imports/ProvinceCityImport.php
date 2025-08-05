<?php

namespace App\Imports;

use App\Models\Province;
use App\Models\City;
use App\Models\District;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ProvinceCityImport implements ToCollection, WithChunkReading
{
    public function collection(Collection $rows)
    {
        $rows = $rows->skip(1); // حذف ردیف هدر

        $currentProvince = null;
        $currentCity = null;
        $cityDeprivation = null;

        foreach ($rows as $index => $row) {
            try {
                $provinceName = trim($row[0] ?? '');
                $cityName = trim($row[1] ?? '');
                $cityStatus = trim($row[2] ?? '');
                $districtName = trim($row[3] ?? '');
                $districtStatus = trim($row[4] ?? '');

                // اگر نام استان جدید وجود دارد، آن را به‌روزرسانی کن
                if (!empty($provinceName)) {
                    $currentProvince = $provinceName;
                }
                
                // اگر نام شهرستان جدید وجود دارد، آن را به‌روزرسانی کن
                if (!empty($cityName)) {
                    $currentCity = $cityName;
                    $cityDeprivation = ($cityStatus === 'محروم');
                }

                // اگر هیچ‌کدام از مقادیر اصلی وجود نداشته باشد، این ردیف را رد کن
                if (empty($currentProvince) || empty($currentCity) || empty($districtName)) {
                    Log::info('Skipping row due to missing data', [
                        'row_index' => $index + 2, // +2 برای شماره ردیف واقعی (هدر + شروع از 1)
                        'province' => $currentProvince,
                        'city' => $currentCity,
                        'district' => $districtName,
                    ]);
                    continue;
                }

                $districtDeprivation = ($districtStatus === 'محروم');

                DB::transaction(function () use (
                    $currentProvince,
                    $currentCity,
                    $cityDeprivation,
                    $districtName,
                    $districtDeprivation,
                    $index
                ) {
                    $province = Province::firstOrCreate(['name' => $currentProvince]);

                    $city = City::updateOrCreate(
                        ['name' => $currentCity, 'province_id' => $province->id],
                        ['is_deprived' => $cityDeprivation]
                    );

                    District::updateOrCreate(
                        ['name' => $districtName, 'city_id' => $city->id],
                        ['is_deprived' => $districtDeprivation]
                    );
                    
                    Log::info('Successfully imported row', [
                        'row_index' => $index + 2,
                        'province' => $currentProvince,
                        'city' => $currentCity,
                        'district' => $districtName,
                    ]);
                });

            } catch (\Throwable $e) {
                Log::error('Error importing row: ' . $e->getMessage(), [
                    'row_index' => $index + 2,
                    'row' => $row->toArray(),
                    'current_province' => $currentProvince ?? 'null',
                    'current_city' => $currentCity ?? 'null',
                ]);
            }
        }
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
