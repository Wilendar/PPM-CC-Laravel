<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== FAILED JOBS ===" . PHP_EOL . PHP_EOL;

$failedJobs = DB::table('failed_jobs')->orderBy('failed_at', 'desc')->limit(5)->get();

if ($failedJobs->isEmpty()) {
    echo "No failed jobs." . PHP_EOL;
} else {
    foreach ($failedJobs as $job) {
        echo "Failed Job #" . $job->id . PHP_EOL;
        echo "  UUID: " . $job->uuid . PHP_EOL;
        echo "  Connection: " . $job->connection . PHP_EOL;
        echo "  Queue: " . $job->queue . PHP_EOL;
        echo "  Failed At: " . $job->failed_at . PHP_EOL;
        echo "  Exception:" . PHP_EOL;

        // Get first 500 chars of exception
        $exception = substr($job->exception, 0, 1000);
        echo "    " . str_replace("\n", "\n    ", $exception) . PHP_EOL;
        if (strlen($job->exception) > 1000) {
            echo "    ... (truncated)" . PHP_EOL;
        }
        echo PHP_EOL;
    }
}
