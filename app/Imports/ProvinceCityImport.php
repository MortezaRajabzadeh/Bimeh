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

                if ($provinceName) $currentProvince = $provinceName;
                if ($cityName) {
                    $currentCity = $cityName;
                    $cityDeprivation = ($cityStatus === 'محروم');
                }

                if (!$currentProvince || !$currentCity || !$districtName) {
                    Log::warning("⛔ ردیف ناقص در $index: " . json_encode($row));
                    continue;
                }

                $districtDeprivation = ($districtStatus === 'محروم');

                DB::transaction(function () use (
                    $currentProvince,
                    $currentCity,
                    $cityDeprivation,
                    $districtName,
                    $districtDeprivation
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
                });

            } catch (\Throwable $e) {
                Log::error("❌ خطا در ردیف $index: " . $e->getMessage(), [
                    'row' => $row->toArray(),
                ]);
            }
        }

        Log::info('✅ ایمپورت chunk با موفقیت انجام شد');
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
