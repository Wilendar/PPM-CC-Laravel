<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== OSTATNIE 10 SYNC_JOBS ===\n\n";

$jobs = DB::table('sync_jobs')
    ->orderByDesc('id')
    ->limit(10)
    ->get(['id', 'job_type', 'job_name', 'target_type', 'status', 'created_at']);

foreach ($jobs as $job) {
    echo "ID: {$job->id}\n";
    echo "  Type: {$job->job_type} | Target: {$job->target_type}\n";
    echo "  Name: " . substr($job->job_name ?? '', 0, 60) . "\n";
    echo "  Status: {$job->status} | Created: {$job->created_at}\n";
    echo "---\n";
}
