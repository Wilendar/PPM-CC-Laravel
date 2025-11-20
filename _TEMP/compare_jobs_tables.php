<?php
/**
 * Compare jobs vs sync_jobs tables
 * Analyze if we're duplicating functionality
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== JOBS vs SYNC_JOBS COMPARISON ===\n\n";

// 1. Column comparison
echo "--- JOBS TABLE (Laravel Queue Infrastructure) ---\n";
$jobsCols = DB::select("SHOW COLUMNS FROM jobs");
foreach ($jobsCols as $col) {
    echo sprintf("%-20s | %s\n", $col->Field, $col->Type);
}

echo "\n--- SYNC_JOBS TABLE (Business Sync Tracking) ---\n";
$syncJobsCols = DB::select("SHOW COLUMNS FROM sync_jobs");
$uniqueCols = [];
foreach ($syncJobsCols as $col) {
    $uniqueCols[] = $col->Field;
    if (in_array($col->Field, ['id', 'created_at', 'updated_at'])) {
        continue; // Skip common columns
    }
    echo sprintf("%-30s | %s\n", $col->Field, $col->Type);
}

// 2. Purpose analysis
echo "\n=== FUNCTIONAL DIFFERENCES ===\n\n";

echo "JOBS table purpose:\n";
echo "- Laravel queue infrastructure\n";
echo "- Temporary storage (deleted after execution)\n";
echo "- Handles ALL queue jobs (not just sync)\n";
echo "- No business logic tracking\n";
echo "- Minimal columns: " . count($jobsCols) . "\n\n";

echo "SYNC_JOBS table purpose:\n";
echo "- Business sync tracking (permanent log)\n";
echo "- Audit trail & analytics\n";
echo "- Progress tracking (%, items processed)\n";
echo "- Error details & retry logic\n";
echo "- Performance metrics (duration, memory, CPU)\n";
echo "- Rich columns: " . count($syncJobsCols) . "\n\n";

// 3. Unique value in sync_jobs
echo "=== UNIQUE VALUE IN SYNC_JOBS ===\n\n";
$uniqueFeatures = [
    'job_type' => 'Business categorization (product_sync, category_sync, etc.)',
    'progress_percentage' => 'Real-time progress (0-100%)',
    'total_items' => 'Batch processing tracking',
    'processed_items' => 'Items completed',
    'successful_items' => 'Success count',
    'failed_items' => 'Failure count',
    'error_message' => 'User-friendly error messages',
    'error_details' => 'Technical error details',
    'stack_trace' => 'Debug information',
    'retry_count' => 'Retry attempts tracking',
    'next_retry_at' => 'Scheduled retry time',
    'duration_seconds' => 'Performance monitoring',
    'memory_peak_mb' => 'Resource usage',
    'api_calls_made' => 'External API tracking',
    'result_summary' => 'Business results (JSON)',
    'affected_records' => 'Changed records tracking',
    'validation_errors' => 'Data quality issues',
    'warnings' => 'Non-blocking issues',
    'trigger_type' => 'Manual vs Auto vs Scheduled',
    'user_id' => 'Who triggered the sync',
    'notify_on_completion' => 'Email notifications',
    'parent_job_id' => 'Job dependencies',
    'batch_id' => 'Bulk operations grouping',
];

foreach ($uniqueFeatures as $col => $purpose) {
    if (in_array($col, $uniqueCols)) {
        echo "✅ {$col}: {$purpose}\n";
    }
}

// 4. Overlap analysis
echo "\n=== OVERLAP ANALYSIS ===\n\n";
$overlap = [
    'queue_job_id' => 'Links sync_jobs → jobs (foreign key)',
    'queue_attempts' => 'Mirrors jobs.attempts',
    'queue_name' => 'Mirrors jobs.queue',
];

foreach ($overlap as $col => $note) {
    echo "⚠️  {$col}: {$note}\n";
}

// 5. Recommendation
echo "\n=== RECOMMENDATION ===\n\n";

$jobsCount = DB::table('jobs')->count();
$syncJobsCount = DB::table('sync_jobs')->count();
$syncJobsWithQueueId = DB::table('sync_jobs')->whereNotNull('queue_job_id')->count();

echo "Current state:\n";
echo "- jobs table: {$jobsCount} entries (temporary queue)\n";
echo "- sync_jobs table: {$syncJobsCount} entries (permanent log)\n";
echo "- sync_jobs linked to queue: {$syncJobsWithQueueId} ({$syncJobsWithQueueId}/{$syncJobsCount})\n\n";

if ($syncJobsWithQueueId == 0) {
    echo "🚨 PROBLEM: Zero sync_jobs linked to queue jobs!\n";
    echo "   This means sync_jobs are NOT integrated with Laravel Queue.\n\n";
}

echo "OPTION 1: Keep both tables (CURRENT)\n";
echo "  ✅ Pros: Rich business tracking, audit trail, analytics\n";
echo "  ❌ Cons: Complexity, need to maintain sync between tables\n";
echo "  💡 Best for: Enterprise apps needing detailed sync analytics\n\n";

echo "OPTION 2: Use ONLY jobs table\n";
echo "  ✅ Pros: Simpler architecture, no duplication\n";
echo "  ❌ Cons: Lose progress tracking, error details, audit trail\n";
echo "  ❌ Cons: jobs are deleted after execution (no history)\n";
echo "  💡 Best for: Simple queue operations without business tracking\n\n";

echo "OPTION 3: Hybrid - jobs + product_shop_data.sync_status\n";
echo "  ✅ Pros: Simpler than OPTION 1, keeps sync status per product\n";
echo "  ❌ Cons: Lose batch tracking, progress %, retry logic\n";
echo "  💡 Best for: Simple sync with status tracking\n\n";

echo "RECOMMENDATION for PPM-CC-Laravel:\n";
if ($syncJobsCount > 100) {
    echo "  → KEEP both tables (you have {$syncJobsCount} sync_jobs = valuable audit trail)\n";
    echo "  → FIX integration: Ensure SyncProductToPrestaShop creates sync_job record\n";
    echo "  → USE sync_jobs for: Analytics, monitoring, user notifications\n";
} else {
    echo "  → CONSIDER Option 3 (Hybrid) - you have only {$syncJobsCount} sync_jobs\n";
    echo "  → Use jobs table + product_shop_data.sync_status\n";
    echo "  → Remove sync_jobs table if not using analytics/audit features\n";
}
