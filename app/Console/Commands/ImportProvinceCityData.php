<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Imports\ProvinceCityImport;
use Maatwebsite\Excel\Facades\Excel;

class ImportProvinceCityData extends Command
{
    protected $signature = 'provincecity:import {file}';
    protected $description = '📥 ایمپورت اطلاعات استان‌ها، شهرستان‌ها و دهستان‌ها از فایل اکسل';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("❌ فایل '$file' پیدا نشد.");
            return 1;
        }

        try {
            Excel::import(new ProvinceCityImport, $file);
            $this->info('✅ داده‌ها با موفقیت ایمپورت شدند!');
        } catch (\Throwable $e) {
            $this->error("❌ خطا در ایمپورت: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
