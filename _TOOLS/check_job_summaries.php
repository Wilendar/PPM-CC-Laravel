<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\SyncJob;

echo "=== OSTATNIE 10 JOBOW (WSZYSTKIE TYPY) ===\n\n";

$jobs = SyncJob::orderByDesc('created_at')
    ->take(10)
    ->get();

foreach ($jobs as $job) {
    echo "ID: {$job->id}\n";
    echo "  Type: {$job->job_type}\n";
    echo "  Shop: {$job->shop_id}\n";
    echo "  Status: {$job->status}\n";
    echo "  Created: {$job->created_at}\n";
    echo "  Job Data: " . json_encode($job->job_data ?? [], JSON_PRETTY_PRINT) . "\n";
    echo "  Result Summary: " . json_encode($job->result_summary ?? [], JSON_PRETTY_PRINT) . "\n";
    echo "---\n\n";
}
