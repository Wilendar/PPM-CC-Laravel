<?php
/**
 * Verify SyncJob Linkage - PRODUCTION VERIFICATION
 *
 * Checks if sync_jobs are being created and linked to queue jobs
 *
 * Run on production via:
 * plink ... "cd domains/.../public_html && php _TEMP/verify_syncjob_linkage_production.php"
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SYNC_JOBS LINKAGE VERIFICATION (PRODUCTION) ===\n\n";

// Check recent jobs in queue
echo "[1/4] Recent jobs in queue table:\n";
$recentJobs = DB::table('jobs')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get(['id', 'queue', 'payload', 'attempts', 'created_at']);

foreach ($recentJobs as $job) {
    $payload = json_decode($job->payload, true);
    $jobClass = $payload['displayName'] ?? 'Unknown';
    echo sprintf("  Job ID: %d | Class: %s | Queue: %s | Created: %s\n",
        $job->id,
        $jobClass,
        $job->queue,
        date('Y-m-d H:i:s', $job->created_at)
    );
}

// Check recent sync_jobs
echo "\n[2/4] Recent sync_jobs table:\n";
$recentSyncJobs = DB::table('sync_jobs')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['id', 'job_type', 'status', 'queue_job_id', 'source_id', 'target_id', 'created_at']);

if ($recentSyncJobs->isEmpty()) {
    echo "  ⚠️  NO sync_jobs found (table empty)\n";
} else {
    foreach ($recentSyncJobs as $sj) {
        echo sprintf("  SyncJob ID: %d | Type: %s | Status: %s | Queue Job ID: %s | Created: %s\n",
            $sj->id,
            $sj->job_type,
            $sj->status,
            $sj->queue_job_id ?: 'NULL',
            $sj->created_at
        );
    }
}

// Check linkage percentage
echo "\n[3/4] Linkage Analysis:\n";
$totalSyncJobs = DB::table('sync_jobs')->count();
$linkedSyncJobs = DB::table('sync_jobs')->whereNotNull('queue_job_id')->count();

if ($totalSyncJobs > 0) {
    $linkagePercentage = round(($linkedSyncJobs / $totalSyncJobs) * 100, 2);
    echo "  Total sync_jobs: {$totalSyncJobs}\n";
    echo "  Linked to queue: {$linkedSyncJobs} ({$linkagePercentage}%)\n";

    if ($linkagePercentage == 100) {
        echo "  ✅ PERFECT - All sync_jobs linked to queue!\n";
    } elseif ($linkagePercentage > 0) {
        echo "  ⚠️  PARTIAL - Fix is working for new jobs\n";
    } else {
        echo "  ❌ FAIL - No linkage (fix not working)\n";
    }
} else {
    echo "  ℹ️  No sync_jobs in table yet (waiting for first sync)\n";
}

// Check product_shop_data pending status
echo "\n[4/4] Products pending sync:\n";
$pendingProducts = DB::table('product_shop_data')
    ->where('sync_status', 'pending')
    ->limit(5)
    ->get(['product_id', 'shop_id', 'sync_status', 'updated_at']);

if ($pendingProducts->isEmpty()) {
    echo "  No products pending sync\n";
} else {
    foreach ($pendingProducts as $p) {
        echo sprintf("  Product ID: %d | Shop ID: %d | Status: %s | Updated: %s\n",
            $p->product_id,
            $p->shop_id,
            $p->sync_status,
            $p->updated_at
        );
    }
}

echo "\n=== VERIFICATION COMPLETE ===\n";
