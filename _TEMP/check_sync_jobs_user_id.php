<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SYNC_JOBS USER_ID AUDIT ===\n\n";

$stats = \App\Models\SyncJob::selectRaw('
    COUNT(*) as total_jobs,
    SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) as null_user_id,
    SUM(CASE WHEN user_id IS NOT NULL THEN 1 ELSE 0 END) as has_user_id,
    COUNT(DISTINCT user_id) as unique_users
')->first();

echo "Total jobs: {$stats->total_jobs}\n";
echo "Jobs WITH user_id: {$stats->has_user_id}\n";
echo "Jobs WITHOUT user_id (NULL): {$stats->null_user_id}\n";
echo "Unique users: {$stats->unique_users}\n\n";

echo "=== RECENT JOBS (last 10) ===\n\n";

$recent = \App\Models\SyncJob::with('user')
    ->latest()
    ->take(10)
    ->get();

foreach ($recent as $job) {
    $userName = $job->user ? $job->user->name : 'NULL';
    $trigger = $job->trigger_type ?? 'N/A';

    echo "Job #{$job->id}: {$job->job_name}\n";
    echo "  User ID: " . ($job->user_id ?? 'NULL') . " ({$userName})\n";
    echo "  Trigger: {$trigger}\n";
    echo "  Status: {$job->status}\n";
    echo "  Created: {$job->created_at}\n\n";
}

echo "=== TRIGGER TYPE BREAKDOWN ===\n\n";

$triggers = \App\Models\SyncJob::selectRaw('
    trigger_type,
    COUNT(*) as count,
    SUM(CASE WHEN user_id IS NULL THEN 1 ELSE 0 END) as null_users
')->groupBy('trigger_type')
->get();

foreach ($triggers as $trigger) {
    $type = $trigger->trigger_type ?? 'NULL';
    echo "{$type}: {$trigger->count} jobs ({$trigger->null_users} without user_id)\n";
}
