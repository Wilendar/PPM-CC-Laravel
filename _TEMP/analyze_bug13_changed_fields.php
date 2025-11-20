<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== BUG #13 ANALYSIS: Changed Fields Tracking ===\n\n";

// Get recent sync jobs with changed_fields
$jobs = DB::table('sync_jobs')
    ->whereNotNull('changed_fields')
    ->where('changed_fields', '!=', '{}')
    ->where('changed_fields', '!=', '[]')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'job_name', 'changed_fields', 'created_at']);

echo "Recent SyncJobs with Changed Fields:\n\n";

foreach ($jobs as $job) {
    echo "SyncJob #{$job->id} - {$job->job_name}\n";
    echo "  Created: {$job->created_at}\n";

    $changedFields = json_decode($job->changed_fields, true);

    if (is_array($changedFields) && !empty($changedFields)) {
        echo "  Changed Fields:\n";
        foreach ($changedFields as $field => $change) {
            if (is_array($change)) {
                $old = $change['old'] ?? 'N/A';
                $new = $change['new'] ?? 'N/A';
                echo "    - {$field}: {$old} → {$new}\n";
            } else {
                echo "    - {$field}: {$change}\n";
            }
        }
    } else {
        echo "  Changed Fields: " . $job->changed_fields . "\n";
    }

    echo "\n";
}

echo "\n=== Field Name Analysis ===\n\n";

// Collect all unique field names
$allFieldNames = [];

foreach ($jobs as $job) {
    $changedFields = json_decode($job->changed_fields, true);

    if (is_array($changedFields)) {
        foreach ($changedFields as $field => $change) {
            if (!in_array($field, $allFieldNames)) {
                $allFieldNames[] = $field;
            }
        }
    }
}

echo "Unique field names found:\n";
foreach ($allFieldNames as $field) {
    echo "  - {$field}\n";
}

echo "\n";
