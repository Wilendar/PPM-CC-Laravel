<?php
/**
 * Fix Stuck Jobs Script
 *
 * 1. Cancel all pending sync_jobs (except currently running)
 * 2. Mark stuck "running" jobs as failed if older than 1 hour
 *
 * Upload and run: php _TOOLS/fix_stuck_jobs.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== FIX STUCK JOBS ===\n";
echo "Date: " . now()->format('Y-m-d H:i:s') . "\n\n";

// 1. Get current running jobs (keep them)
$runningJobs = DB::table('sync_jobs')
    ->where('status', 'running')
    ->where('created_at', '>', now()->subHours(2)) // Running less than 2 hours = legitimate
    ->pluck('id')
    ->toArray();

echo "Currently running jobs (kept): " . count($runningJobs) . "\n";
if ($runningJobs) {
    echo "  IDs: " . implode(', ', $runningJobs) . "\n";
}

// 2. Cancel all pending jobs
$pendingCount = DB::table('sync_jobs')
    ->where('status', 'pending')
    ->count();

echo "\nPending jobs to cancel: {$pendingCount}\n";

$cancelled = DB::table('sync_jobs')
    ->where('status', 'pending')
    ->update([
        'status' => 'cancelled',
        'error_message' => 'Cancelled by cleanup script - stuck in pending state',
        'completed_at' => now(),
        'updated_at' => now(),
    ]);

echo "Cancelled: {$cancelled} pending jobs\n";

// 3. Mark stuck running jobs as failed (older than 1 hour)
$stuckRunning = DB::table('sync_jobs')
    ->where('status', 'running')
    ->where('created_at', '<', now()->subHours(1))
    ->count();

echo "\nStuck running jobs (>1 hour old): {$stuckRunning}\n";

$failed = DB::table('sync_jobs')
    ->where('status', 'running')
    ->where('created_at', '<', now()->subHours(1))
    ->update([
        'status' => 'failed',
        'error_message' => 'Failed by cleanup script - stuck in running state >1 hour (likely OOM crash)',
        'completed_at' => now(),
        'updated_at' => now(),
    ]);

echo "Marked as failed: {$failed} stuck running jobs\n";

// 4. Final status
echo "\n=== FINAL STATUS ===\n";
$stats = DB::select('SELECT status, COUNT(*) as cnt FROM sync_jobs GROUP BY status ORDER BY status');
foreach ($stats as $row) {
    echo $row->status . ': ' . $row->cnt . "\n";
}

echo "\n=== DONE ===\n";
