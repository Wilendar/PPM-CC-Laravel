<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobProgress;
use App\Models\CategoryPreview;
use Illuminate\Support\Facades\DB;

class DiagnoseStuckJobs extends Command
{
    protected $signature = 'jobs:diagnose {progressIds*}';
    protected $description = 'Diagnose stuck jobs by progress IDs';

    public function handle()
    {
        $progressIds = $this->argument('progressIds');

        $this->info('=== DIAGNOZA WISZACYCH JOBS ===');
        $this->newLine();

        foreach ($progressIds as $progressId) {
            $this->diagnoseSingleJob($progressId);
            $this->newLine();
        }

        // Check failed_jobs table
        $this->info('=== FAILED JOBS CHECK ===');
        $failedJobs = DB::table('failed_jobs')
            ->where(function($query) {
                $query->where('payload', 'LIKE', '%52f073df-c7b4-4e5a-b626-963d6f86e52a%')
                      ->orWhere('payload', 'LIKE', '%8ad85efe-ef10-4784-b0ee-340e6bd3e589%');
            })
            ->get(['id', 'exception', 'failed_at']);

        if ($failedJobs->isEmpty()) {
            $this->info('No entries in failed_jobs table');
        } else {
            foreach ($failedJobs as $failed) {
                $this->error("Failed Job ID: {$failed->id}");
                $this->line("Failed At: {$failed->failed_at}");
                $this->line("Exception: " . substr($failed->exception, 0, 300));
                $this->newLine();
            }
        }

        // Check jobs queue table
        $this->info('=== JOBS QUEUE CHECK ===');
        $jobsInQueue = DB::table('jobs')->count();
        $this->info("Total jobs in queue: {$jobsInQueue}");

        return 0;
    }

    private function diagnoseSingleJob($progressId)
    {
        $this->info("--- Progress ID: {$progressId} ---");

        $progress = JobProgress::find($progressId);

        if (!$progress) {
            $this->error("Progress record NOT FOUND");
            return;
        }

        $this->line("Job ID: {$progress->job_id}");
        $this->line("Job Type: {$progress->job_type}");
        $this->line("Status: {$progress->status}");
        $this->line("Created: {$progress->created_at->toDateTimeString()}");
        $this->line("Updated: {$progress->updated_at->toDateTimeString()}");

        $timeDiff = $progress->created_at->diffInMinutes(now());
        $this->line("Age: {$timeDiff} minutes");

        if ($progress->progress_data) {
            $this->line("Progress Data: " . json_encode($progress->progress_data, JSON_PRETTY_PRINT));
        }

        // Check CategoryPreview
        $preview = CategoryPreview::where('job_id', $progress->job_id)->first();
        if ($preview) {
            $this->info("CategoryPreview Found:");
            $this->line("  ID: {$preview->id}");
            $this->line("  Status: {$preview->status}");
            $this->line("  Total Categories: {$preview->total_categories}");
            $this->line("  Created: {$preview->created_at->toDateTimeString()}");
            $this->line("  Updated: {$preview->updated_at->toDateTimeString()}");
        } else {
            $this->warn("No CategoryPreview found for this job_id");
        }
    }
}
