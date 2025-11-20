<?php
// Check recent jobs and sync_jobs

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== JOBS TABLE (last 10) ===\n";
$jobs = DB::table('jobs')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

if ($jobs->isEmpty()) {
    echo "No jobs in queue (empty table)\n";
} else {
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $jobClass = $payload['displayName'] ?? 'Unknown';
        echo sprintf(
            "Job ID: %d | Queue: %s | Class: %s | Attempts: %d | Created: %s\n",
            $job->id,
            $job->queue,
            $jobClass,
            $job->attempts,
            $job->created_at
        );
    }
}

echo "\n=== SYNC_JOBS TABLE (last 10) ===\n";
$syncJobs = DB::table('sync_jobs')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['id', 'status', 'queue_job_id', 'target_type', 'target_id', 'created_at']);

foreach ($syncJobs as $sj) {
    echo sprintf(
        "SyncJob: %s | Status: %s | Queue Job ID: %s | Target: %s #%s | Created: %s\n",
        substr($sj->id, 0, 8) . '...',
        $sj->status,
        $sj->queue_job_id ?: 'NULL',
        $sj->target_type,
        $sj->target_id,
        $sj->created_at
    );
}

echo "\n=== PRODUCT_SHOP_DATA (product 11018) ===\n";
$shopData = DB::table('product_shop_data')
    ->where('product_id', 11018)
    ->get(['id', 'shop_id', 'sync_status', 'last_synced_at', 'updated_at']);

foreach ($shopData as $sd) {
    echo sprintf(
        "Shop ID: %d | Sync Status: %s | Last Synced: %s | Updated: %s\n",
        $sd->shop_id,
        $sd->sync_status,
        $sd->last_synced_at ?: 'NULL',
        $sd->updated_at
    );
}

echo "\n=== QUEUE STATS ===\n";
echo "Jobs in queue: " . DB::table('jobs')->count() . "\n";
echo "Sync jobs total: " . DB::table('sync_jobs')->count() . "\n";
echo "Sync jobs (status=pending): " . DB::table('sync_jobs')->where('status', 'pending')->count() . "\n";
echo "Sync jobs (status=processing): " . DB::table('sync_jobs')->where('status', 'processing')->count() . "\n";
echo "Sync jobs (status=completed): " . DB::table('sync_jobs')->where('status', 'completed')->count() . "\n";
echo "Sync jobs (status=cancelled): " . DB::table('sync_jobs')->where('status', 'cancelled')->count() . "\n";
