<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JobProgress;
use App\Models\CategoryPreview;
use App\Models\WorkerHeartbeat;
use Illuminate\Support\Facades\DB;

class CleanupStuckJobProgress extends Command
{
    protected $signature = 'jobs:cleanup-stuck {--dry-run : Show what would be cleaned without making changes} {--minutes=30 : Minutes threshold for stuck jobs}';
    protected $description = 'Cleanup stuck job progress records (pending >30min)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $minutesThreshold = (int) $this->option('minutes');

        $this->info("=== CLEANUP STUCK JOB PROGRESS ===");
        $this->line("Threshold: {$minutesThreshold} minutes");
        $this->line("Mode: " . ($dryRun ? "DRY RUN (no changes)" : "LIVE (will update DB)"));
        $this->newLine();

        // Find stuck jobs: status = pending AND created_at < X minutes ago
        $stuckJobs = JobProgress::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes($minutesThreshold))
            ->with('shop:id,name')
            ->get();

        if ($stuckJobs->isEmpty()) {
            $this->info('No stuck jobs found!');
            return 0;
        }

        $this->warn("Found {$stuckJobs->count()} stuck jobs:");
        $this->newLine();

        $cleaned = 0;

        foreach ($stuckJobs as $job) {
            $this->line("--- Job Progress ID: {$job->id} ---");
            $this->line("  Job ID: {$job->job_id}");
            $this->line("  Type: {$job->job_type}");
            $this->line("  Shop: {$job->shop?->name}");
            $this->line("  Created: {$job->created_at->toDateTimeString()}");
            $this->line("  Age: {$job->created_at->diffInMinutes(now())} minutes");

            // Check if related CategoryPreview exists
            $preview = CategoryPreview::where('job_id', $job->job_id)->first();

            if ($preview) {
                $this->line("  Preview ID: {$preview->id}");
                $this->line("  Preview Status: {$preview->status}");

                // Determine action based on preview status
                $action = null;
                $newStatus = null;

                switch ($preview->status) {
                    case CategoryPreview::STATUS_REJECTED:
                        $action = 'Mark JobProgress as FAILED (preview rejected by user)';
                        $newStatus = 'failed';
                        break;

                    case CategoryPreview::STATUS_PENDING:
                        if ($preview->created_at->diffInMinutes(now()) > 15) {
                            $action = 'Expire preview + mark JobProgress as FAILED';
                            $newStatus = 'failed';
                        } else {
                            $action = 'SKIP (preview still fresh)';
                        }
                        break;

                    case CategoryPreview::STATUS_APPROVED:
                        $action = 'INVESTIGATE (approved but progress not updated - BulkCreateCategories issue?)';
                        break;

                    case CategoryPreview::STATUS_EXPIRED:
                        $action = 'Mark JobProgress as FAILED (preview already expired)';
                        $newStatus = 'failed';
                        break;
                }

                $this->line("  Action: {$action}");

                if ($newStatus && !$dryRun) {
                    // Update JobProgress
                    $job->update([
                        'status' => $newStatus,
                        'completed_at' => now(),
                    ]);

                    // Expire preview if pending
                    if ($preview->status === CategoryPreview::STATUS_PENDING && $newStatus === 'expired') {
                        $preview->update(['status' => CategoryPreview::STATUS_EXPIRED]);
                    }

                    $this->info("  ✅ CLEANED");
                    $cleaned++;
                } elseif ($newStatus && $dryRun) {
                    $this->comment("  [DRY RUN] Would set status to: {$newStatus}");
                }
            } else {
                $this->warn("  No CategoryPreview found!");
                $action = 'Mark JobProgress as FAILED (no preview found)';
                $this->line("  Action: {$action}");

                if (!$dryRun) {
                    $job->update([
                        'status' => 'failed',
                        'completed_at' => now(),
                    ]);
                    $this->info("  ✅ CLEANED");
                    $cleaned++;
                } else {
                    $this->comment("  [DRY RUN] Would mark as failed");
                }
            }

            $this->newLine();
        }

        if ($dryRun) {
            $this->info("DRY RUN complete. Re-run without --dry-run to apply changes.");
        } else {
            $this->info("Cleaned {$cleaned} stuck jobs");
        }

        // === WORKER HEARTBEAT CLEANUP ===
        $this->newLine();
        $this->info("=== WORKER HEARTBEAT CLEANUP ===");

        // Mark stale heartbeats (>300s) as dead
        $staleHeartbeats = WorkerHeartbeat::where('status', 'processing')
            ->where('last_heartbeat_at', '<', now()->subSeconds(300))
            ->get();

        if ($staleHeartbeats->isNotEmpty()) {
            $this->warn("Found {$staleHeartbeats->count()} stale heartbeats:");

            foreach ($staleHeartbeats as $hb) {
                $age = now()->diffInSeconds($hb->last_heartbeat_at);
                $this->line("  Heartbeat #{$hb->id}: job_id={$hb->job_id}, pid={$hb->worker_pid}, age={$age}s");

                if (!$dryRun) {
                    $hb->markDead();

                    // Mark related job_progress as failed if still running
                    if ($hb->job_progress_id) {
                        $jp = JobProgress::find($hb->job_progress_id);
                        if ($jp && $jp->status === 'running') {
                            $jp->update([
                                'status' => 'failed',
                                'completed_at' => now(),
                            ]);
                            $jp->addError('SYSTEM', 'Worker przestal odpowiadac (heartbeat timeout)');
                            $this->info("  -> job_progress #{$jp->id} marked as failed");
                        }
                    }
                }
            }
        } else {
            $this->info("No stale heartbeats found.");
        }

        // Cleanup old heartbeat records (>7 days)
        $oldCount = WorkerHeartbeat::where('created_at', '<', now()->subDays(7))
            ->whereIn('status', ['idle', 'dead'])
            ->count();

        if ($oldCount > 0) {
            $this->line("Found {$oldCount} old heartbeat records (>7 days)");
            if (!$dryRun) {
                WorkerHeartbeat::where('created_at', '<', now()->subDays(7))
                    ->whereIn('status', ['idle', 'dead'])
                    ->delete();
                $this->info("Deleted {$oldCount} old heartbeat records");
            } else {
                $this->comment("[DRY RUN] Would delete {$oldCount} old records");
            }
        }

        return 0;
    }
}
