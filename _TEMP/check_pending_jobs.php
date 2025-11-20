<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== PENDING JOBS W sync_jobs ===\n\n";

$jobs = DB::table('sync_jobs')
    ->whereIn('status', ['pending', 'running', 'paused'])
    ->orderBy('created_at', 'desc')
    ->get();

echo "Total pending/running/paused jobs: " . $jobs->count() . "\n\n";

foreach ($jobs as $job) {
    echo "=== Job ID: {$job->id} ===\n";
    echo "Job ID (unique): {$job->job_id}\n";
    echo "Job Type: {$job->job_type}\n";
    echo "Job Name: {$job->job_name}\n";
    echo "Status: {$job->status}\n";
    echo "Source: {$job->source_type} (ID: {$job->source_id})\n";
    echo "Target: {$job->target_type} (ID: {$job->target_id})\n";
    echo "Created: {$job->created_at}\n";
    echo "Progress: {$job->progress_percentage}%\n";

    // Decode job_data to find product info
    if ($job->job_data) {
        $jobData = json_decode($job->job_data, true);
        echo "Job Data:\n";
        if (isset($jobData['product_id'])) {
            echo "  Product ID: {$jobData['product_id']}\n";
        }
        if (isset($jobData['product_sku'])) {
            echo "  Product SKU: {$jobData['product_sku']}\n";
        }
        if (isset($jobData['shop_id'])) {
            echo "  Shop ID: {$jobData['shop_id']}\n";
        }

        // Check if this is TEST-AUTOFIX
        $isTestAutofix = (isset($jobData['product_sku']) &&
                          strpos($jobData['product_sku'], 'TEST-AUTOFIX-1762422508') !== false);

        if ($isTestAutofix) {
            echo "\n  ✅ ✅ ✅ FOUND TEST-AUTOFIX-1762422508! ✅ ✅ ✅\n";
        }
    }

    echo "\n";
}

// Also check if there are any jobs with TEST-AUTOFIX in job_data
echo "\n=== SEARCHING FOR TEST-AUTOFIX-1762422508 ===\n\n";

$testJobs = DB::table('sync_jobs')
    ->where('job_data', 'LIKE', '%TEST-AUTOFIX-1762422508%')
    ->get();

if ($testJobs->isEmpty()) {
    echo "❌ No jobs found with TEST-AUTOFIX-1762422508 in job_data\n";
} else {
    echo "✅ Found {$testJobs->count()} job(s) with TEST-AUTOFIX-1762422508:\n\n";
    foreach ($testJobs as $job) {
        echo "Job ID: {$job->id} - Status: {$job->status} - Type: {$job->job_type}\n";
        echo "Job Name: {$job->job_name}\n";
        echo "Created: {$job->created_at}\n";
        echo "\n";
    }
}

echo "\n";
