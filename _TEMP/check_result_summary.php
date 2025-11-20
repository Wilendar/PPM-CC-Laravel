<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== LATEST SYNC_JOB RESULT_SUMMARY ===\n\n";

$job = App\Models\SyncJob::latest()->first();

if ($job) {
    echo "Job ID: {$job->id}\n";
    echo "Job Name: {$job->job_name}\n";
    echo "Status: {$job->status}\n";
    echo "Completed At: {$job->completed_at}\n\n";

    echo "Result Summary:\n";
    echo json_encode($job->result_summary, JSON_PRETTY_PRINT);
    echo "\n\n";

    // Check if synced_data exists
    if (isset($job->result_summary['synced_data'])) {
        echo "✓ synced_data EXISTS\n";
        echo "Fields count: " . count($job->result_summary['synced_data']) . "\n";
    } else {
        echo "✗ synced_data MISSING\n";
    }

    // Check if changed_fields exists
    if (isset($job->result_summary['changed_fields'])) {
        echo "✓ changed_fields EXISTS\n";
        echo "Changes count: " . count($job->result_summary['changed_fields']) . "\n";
    } else {
        echo "✗ changed_fields MISSING (expected for first UPDATE after deployment)\n";
    }

} else {
    echo "No sync jobs found.\n";
}
