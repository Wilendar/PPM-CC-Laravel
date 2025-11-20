<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== SYNC_JOBS JOB_DATA STRUCTURE ===\n\n";

// Get recent jobs
$jobs = DB::table('sync_jobs')
    ->whereNotNull('job_data')
    ->where('job_data', '!=', '{}')
    ->where('job_data', '!=', '[]')
    ->orderBy('id', 'desc')
    ->limit(5)
    ->get(['id', 'job_name', 'job_data', 'created_at']);

echo "Recent SyncJobs with job_data:\n\n";

foreach ($jobs as $job) {
    echo "SyncJob #{$job->id} - {$job->job_name}\n";
    echo "  Created: {$job->created_at}\n";

    $jobData = json_decode($job->job_data, true);

    if (is_array($jobData)) {
        echo "  Job Data keys: " . implode(', ', array_keys($jobData)) . "\n";

        // Show pending_fields if exists
        if (isset($jobData['pending_fields'])) {
            echo "  Pending Fields:\n";
            print_r($jobData['pending_fields']);
        }

        // Show changed_fields if exists
        if (isset($jobData['changed_fields'])) {
            echo "  Changed Fields:\n";
            print_r($jobData['changed_fields']);
        }
    } else {
        echo "  Job Data: {$job->job_data}\n";
    }

    echo "\n" . str_repeat('-', 80) . "\n\n";
}
