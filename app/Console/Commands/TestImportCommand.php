<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProvinceCityImport;
use App\Models\Province;
use App\Models\City;
use App\Models\District;

class TestImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test importing Excel file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // بررسی تعداد استان‌ها قبل از import
        $provincesBeforeCount = Province::count();
        $citiesBeforeCount = City::count();
        $districtsBeforeCount = District::count();
        
        $this->info("Before import:");
        $this->line("Provinces: $provincesBeforeCount");
        $this->line("Cities: $citiesBeforeCount");
        $this->line("Districts: $districtsBeforeCount");
        
        // بررسی استان‌های خراسان قبل از import
        $khorasanProvinces = Province::where('name', 'like', '%خراسان%')->get();
        $this->line("Khorasan provinces before: " . $khorasanProvinces->count());
        foreach ($khorasanProvinces as $province) {
            $this->line(" - " . $province->name);
        }
        
        $this->info("\nStarting import...");
        
        try {
            Excel::import(new ProvinceCityImport, 'public/exele/exel.xlsx');
            $this->info("Import completed successfully!");
        } catch (\Throwable $e) {
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }
        
        // بررسی تعداد استان‌ها بعد از import
        $provincesAfterCount = Province::count();
        $citiesAfterCount = City::count();
        $districtsAfterCount = District::count();
        
        $this->info("\nAfter import:");
        $this->line("Provinces: $provincesAfterCount (+ " . ($provincesAfterCount - $provincesBeforeCount) . ")");
        $this->line("Cities: $citiesAfterCount (+ " . ($citiesAfterCount - $citiesBeforeCount) . ")");
        $this->line("Districts: $districtsAfterCount (+ " . ($districtsAfterCount - $districtsBeforeCount) . ")");
        
        // بررسی استان‌های خراسان بعد از import
        $khorasanProvincesAfter = Province::where('name', 'like', '%خراسان%')->get();
        $this->line("\nKhorasan provinces after: " . $khorasanProvincesAfter->count());
        foreach ($khorasanProvincesAfter as $province) {
            $citiesCount = $province->cities()->count();
            $this->line(" - " . $province->name . " (Cities: $citiesCount)");
        }
        
        return 0;
    }
}
