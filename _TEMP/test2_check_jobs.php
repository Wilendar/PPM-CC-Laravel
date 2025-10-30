<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== JOBS QUEUE ===" . PHP_EOL . PHP_EOL;

$jobs = DB::table('jobs')->orderBy('id', 'desc')->limit(5)->get();

if ($jobs->isEmpty()) {
    echo "No jobs in queue." . PHP_EOL;
} else {
    foreach ($jobs as $job) {
        echo "Job #" . $job->id . PHP_EOL;
        echo "  Queue: " . $job->queue . PHP_EOL;
        echo "  Attempts: " . $job->attempts . PHP_EOL;
        echo "  Created: " . date('Y-m-d H:i:s', $job->created_at) . PHP_EOL;

        // Decode payload to see job class
        $payload = json_decode($job->payload, true);
        if (isset($payload['displayName'])) {
            echo "  Job: " . $payload['displayName'] . PHP_EOL;
        }
        echo PHP_EOL;
    }
}
