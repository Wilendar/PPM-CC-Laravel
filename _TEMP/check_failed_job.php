<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$uuid = '672260e5-42ac-4b7f-8e24-56d879230263';

$job = DB::table('failed_jobs')->where('uuid', $uuid)->first();

if (!$job) {
    echo "Job not found: {$uuid}\n";
    exit(1);
}

echo "=== FAILED JOB DETAILS ===\n";
echo "UUID: {$job->uuid}\n";
echo "Connection: {$job->connection}\n";
echo "Queue: {$job->queue}\n";
echo "Failed At: {$job->failed_at}\n";
echo "\n=== EXCEPTION (first 2000 chars) ===\n";
echo substr($job->exception, 0, 2000) . "\n";
