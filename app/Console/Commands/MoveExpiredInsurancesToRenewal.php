<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Family;

class MoveExpiredInsurancesToRenewal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:move-expired-insurances-to-renewal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Move families with expired insurance to renewal
        $count = 0;
        Family::expiredInsurance()->chunk(100, function ($families) use (&$count) {
            foreach ($families as $family) {
                $family->status = 'renewal';
                $family->save();
                $count++;
            }
        });
        
        $this->info("{$count} families moved to renewal status.");
        // (For future: handle members with expired insurance)
        return 0;
    }
}
