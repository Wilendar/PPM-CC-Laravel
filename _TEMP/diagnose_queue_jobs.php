<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== QUEUE JOBS DIAGNOSIS ===\n\n";

// 1. Check jobs table
$jobs = DB::table('jobs')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

echo "Recent jobs in queue (last 10):\n";
echo str_repeat('=', 80) . "\n";

if ($jobs->isEmpty()) {
    echo "❌ NO JOBS IN QUEUE!\n\n";
} else {
    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);
        $jobClass = $payload['displayName'] ?? 'Unknown';

        echo "ID: {$job->id}\n";
        echo "  Queue: {$job->queue}\n";
        echo "  Job: {$jobClass}\n";
        echo "  Attempts: {$job->attempts}\n";
        echo "  Reserved: " . ($job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : 'NULL') . "\n";
        echo "  Available: " . date('Y-m-d H:i:s', $job->available_at) . "\n";
        echo "  Created: " . date('Y-m-d H:i:s', $job->created_at) . "\n";
        echo str_repeat('-', 80) . "\n";
    }
}

// 2. Check failed_jobs
$failedJobs = DB::table('failed_jobs')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get();

echo "\nRecent failed jobs (last 5):\n";
echo str_repeat('=', 80) . "\n";

if ($failedJobs->isEmpty()) {
    echo "✅ No failed jobs\n";
} else {
    foreach ($failedJobs as $job) {
        $payload = json_decode($job->payload, true);
        $jobClass = $payload['displayName'] ?? 'Unknown';

        echo "UUID: {$job->uuid}\n";
        echo "  Job: {$jobClass}\n";
        echo "  Exception: " . substr($job->exception, 0, 200) . "...\n";
        echo "  Failed at: {$job->failed_at}\n";
        echo str_repeat('-', 80) . "\n";
    }
}

// 3. Check queue configuration
echo "\nQueue Configuration:\n";
echo str_repeat('=', 80) . "\n";
echo "QUEUE_CONNECTION: " . config('queue.default') . "\n";
echo "Database driver: " . config('queue.connections.database.driver') . "\n";
echo "Table: " . config('queue.connections.database.table') . "\n";

// 4. Check if queue worker is running (via recent job processing)
$recentProcessed = DB::table('jobs')
    ->whereNotNull('reserved_at')
    ->where('reserved_at', '>', now()->subMinutes(5)->timestamp)
    ->count();

echo "\nQueue Worker Status:\n";
echo str_repeat('=', 80) . "\n";

if ($recentProcessed > 0) {
    echo "✅ Queue worker IS RUNNING (processed {$recentProcessed} jobs in last 5 min)\n";
} else {
    echo "⚠️  Queue worker MAY NOT BE RUNNING (no jobs processed in last 5 min)\n";
    echo "    Check crontab: * * * * * php artisan queue:work database --stop-when-empty\n";
}

echo "\n=== DIAGNOSIS COMPLETE ===\n";
