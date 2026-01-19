<?php
// Check sync_jobs status on production
// Upload and run: php _TOOLS/check_jobs.php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SYNC_JOBS STATUS ===\n";
$stats = DB::select('SELECT status, COUNT(*) as cnt FROM sync_jobs GROUP BY status');
foreach ($stats as $row) {
    echo $row->status . ': ' . $row->cnt . "\n";
}
echo "Total: " . DB::table('sync_jobs')->count() . "\n";

echo "\n=== PENDING/RUNNING JOBS ===\n";
$pending = DB::table('sync_jobs')
    ->whereIn('status', ['pending', 'running'])
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['id', 'job_name', 'status', 'created_at']);

foreach ($pending as $job) {
    echo "[{$job->id}] {$job->status} - {$job->job_name} ({$job->created_at})\n";
}

echo "\n=== JOB_PROGRESS STATUS ===\n";
if (Schema::hasTable('job_progress')) {
    $progress = DB::select('SELECT status, COUNT(*) as cnt FROM job_progress GROUP BY status');
    foreach ($progress as $row) {
        echo $row->status . ': ' . $row->cnt . "\n";
    }
} else {
    echo "Table job_progress does not exist\n";
}

echo "\n=== QUEUE TABLES ===\n";
echo "jobs table: " . (Schema::hasTable('jobs') ? 'EXISTS' : 'NOT FOUND') . "\n";
echo "failed_jobs table: " . (Schema::hasTable('failed_jobs') ? 'EXISTS' : 'NOT FOUND') . "\n";
