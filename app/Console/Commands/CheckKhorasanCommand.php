<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Province;
use App\Models\City;
use App\Models\District;

class CheckKhorasanCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:khorasan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Khorasan provinces data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $khorasanProvinces = Province::where('name', 'like', '%خراسان%')
            ->with(['cities' => function($query) {
                $query->with('districts');
            }])
            ->get();
        
        $this->info('Khorasan Provinces Data:');
        $this->line('========================');
        
        foreach ($khorasanProvinces as $province) {
            $this->line("\n🏛️  {$province->name}");
            $this->line("   Cities: {$province->cities->count()}");
            
            foreach ($province->cities as $city) {
                $deprivedStatus = $city->is_deprived ? '🔴 محروم' : '🟢 برخوردار';
                $this->line("     🏙️  {$city->name} - {$deprivedStatus} (Districts: {$city->districts->count()})");
                
                // نمایش چند دهستان اول
                foreach ($city->districts->take(3) as $district) {
                    $districtStatus = $district->is_deprived ? '🔴' : '🟢';
                    $this->line("         🏘️  {$district->name} {$districtStatus}");
                }
                
                if ($city->districts->count() > 3) {
                    $remaining = $city->districts->count() - 3;
                    $this->line("         ... and {$remaining} more districts");
                }
            }
        }
        
        // آمار کلی
        $totalCities = $khorasanProvinces->sum(function($p) { return $p->cities->count(); });
        $totalDistricts = $khorasanProvinces->sum(function($p) { 
            return $p->cities->sum(function($c) { return $c->districts->count(); }); 
        });
        
        $this->line("\n📊 Statistics:");
        $this->line("   Provinces: {$khorasanProvinces->count()}");
        $this->line("   Total Cities: {$totalCities}");
        $this->line("   Total Districts: {$totalDistricts}");
        
        return 0;
    }
}
