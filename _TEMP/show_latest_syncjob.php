<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$job = DB::table('sync_jobs')->orderBy('id', 'desc')->first();

if ($job) {
    echo "Latest SyncJob:\n";
    echo "  ID: {$job->id}\n";
    echo "  Type: {$job->job_type}\n";
    echo "  Status: {$job->status}\n";
    echo "  Source ID: {$job->source_id} (Product)\n";
    echo "  Target ID: {$job->target_id} (Shop)\n";
    echo "  Created: {$job->created_at}\n";
    echo "  Progress: {$job->progress_percentage}%\n";
} else {
    echo "No sync_jobs found\n";
}
