<?php

namespace App\Services;

use Maatwebsite\Excel\Facades\Excel;

class ProvinceCityService
{
    protected $filePath = 'exele/exel.xlsx';
    protected $data = null;
    protected $header = null;

    protected function loadData()
    {
        if ($this->data !== null) {
            return;
        }
        $raw = Excel::toArray([], public_path($this->filePath))[0];
        $this->header = $raw[0];
        $this->data = array_slice($raw, 1); // حذف ردیف عنوان
    }

    public function getProvinces(): array
    {
        $this->loadData();
        $provinces = collect($this->data)
            ->pluck(0) // ایندکس ستون استان
            ->unique()
            ->filter()
            ->values()
            ->toArray();
        return $provinces;
    }

    public function getCitiesByProvince(string $province): array
    {
        $this->loadData();
        $cities = collect($this->data)
            ->where(0, $province)
            ->flatMap(function ($row) {
                $result = [];
                if (!empty($row[1])) $result[] = $row[1]; // شهرستان
                if (!empty($row[3])) $result[] = $row[3]; // نام دهستان
                return $result;
            })
            ->unique()
            ->filter()
            ->values()
            ->toArray();
        return $cities;
    }

    public function getProvinceCityMap(): array
    {
        $this->loadData();
        $map = [];
        foreach ($this->data as $row) {
            if (!empty($row[0])) {
                if (!empty($row[1])) $map[$row[0]][] = $row[1]; // شهرستان
                if (!empty($row[3])) $map[$row[0]][] = $row[3]; // نام دهستان
            }
        }
        foreach ($map as $prov => $cities) {
            $map[$prov] = array_values(array_unique($cities));
        }
        return $map;
    }
} 
