<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class TestExcelCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:excel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Excel file structure';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $data = Excel::toCollection(new Collection, 'public/exele/exel.xlsx');
        
        $this->info('First 15 rows of Excel file:');
        
        foreach ($data[0]->take(15) as $index => $row) {
            $rowData = $row->toArray();
            $this->line("Row $index: " . json_encode($rowData, JSON_UNESCAPED_UNICODE));
        }
        
        // بررسی داده‌های خراسان
        $this->info('\nLooking for Khorasan data:');
        foreach ($data[0] as $index => $row) {
            $rowData = $row->toArray();
            $provinceName = trim($rowData[0] ?? '');
            
            if (stripos($provinceName, 'خراسان') !== false) {
                $this->line("Found Khorasan at row $index: " . json_encode($rowData, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
