<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== SYMULACJA QUERY Z SyncController ===\n\n";

// This is the query SyncController uses to fetch recent jobs
// Based on getRecentSyncJobsProperty() method

$recentJobs = DB::table('sync_jobs')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['id', 'job_type', 'job_name', 'status', 'target_id', 'created_at', 'progress_percentage', 'user_id']);

echo "Recent Sync Jobs (SyncController query):\n";
echo "Total: " . $recentJobs->count() . "\n\n";

foreach ($recentJobs as $job) {
    $highlight = ($job->id == 92) ? ' ← ✅ OUR JOB!' : '';
    echo "Job ID: {$job->id}{$highlight}\n";
    echo "  Type: {$job->job_type}\n";
    echo "  Name: {$job->job_name}\n";
    echo "  Status: {$job->status}\n";
    echo "  Shop ID: {$job->target_id}\n";
    echo "  Progress: {$job->progress_percentage}%\n";
    echo "  User: " . ($job->user_id ?? 'NULL') . "\n";
    echo "  Created: {$job->created_at}\n";
    echo "\n";
}

// Check if job #92 would be visible in the UI
$job92 = $recentJobs->where('id', 92)->first();

if ($job92) {
    echo "✅ JOB #92 IS VISIBLE in recent jobs!\n";
    echo "✅ Should appear in /admin/shops/sync UI\n";
} else {
    echo "❌ JOB #92 NOT FOUND in recent jobs query\n";
    echo "⚠️ Might be filtered out or too old\n";
}

echo "\n";
