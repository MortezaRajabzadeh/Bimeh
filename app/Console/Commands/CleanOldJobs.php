<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CleanOldJobs extends Command
{
    protected $signature = 'queue:clean-old {--days=7}';
    protected $description = 'Clean old completed and failed jobs';

    public function handle()
    {
        $days = $this->option('days');
        $cutoff = Carbon::now()->subDays($days);
        
        // پاکسازی job های موفق
        $deletedJobs = DB::table('jobs')
            ->where('created_at', '<', $cutoff)
            ->delete();
            
        // پاکسازی job های ناموفق
        $deletedFailedJobs = DB::table('failed_jobs')
            ->where('failed_at', '<', $cutoff)
            ->delete();
            
        $this->info("Deleted {$deletedJobs} completed jobs and {$deletedFailedJobs} failed jobs older than {$days} days.");
    }
}