<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== SPRAWDZENIE LARAVEL JOBS QUEUE ===\n\n";

// Check if jobs table exists
$jobsTableExists = DB::select("SHOW TABLES LIKE 'jobs'");

if (empty($jobsTableExists)) {
    echo "❌ Tabela 'jobs' NIE ISTNIEJE!\n";
    echo "⚠️ Laravel queue system nie jest skonfigurowany lub migracja nie została uruchomiona.\n";
    exit;
}

echo "✅ Tabela 'jobs' istnieje\n\n";

// Count jobs
$totalJobs = DB::table('jobs')->count();

echo "=== JOBS W KOLEJCE ===\n";
echo "Total: $totalJobs jobs\n\n";

if ($totalJobs > 0) {
    $jobs = DB::table('jobs')
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();

    foreach ($jobs as $job) {
        $payload = json_decode($job->payload, true);

        echo "Job ID: {$job->id}\n";
        echo "  Queue: {$job->queue}\n";
        echo "  Attempts: {$job->attempts}\n";
        echo "  Reserved: " . ($job->reserved_at ?? 'NULL') . "\n";
        echo "  Available At: " . date('Y-m-d H:i:s', $job->available_at) . "\n";
        echo "  Created At: " . date('Y-m-d H:i:s', $job->created_at) . "\n";

        if (isset($payload['displayName'])) {
            echo "  Job Type: {$payload['displayName']}\n";
        }

        // Check if this is SyncProductToPrestaShop
        if (strpos($payload['displayName'] ?? '', 'SyncProductToPrestaShop') !== false) {
            echo "  ✅ ✅ ✅ FOUND SyncProductToPrestaShop JOB! ✅ ✅ ✅\n";

            $jobData = $payload['data']['command'] ?? null;
            if ($jobData) {
                // Parse serialized object (simplified)
                if (strpos($jobData, 'TEST-AUTOFIX') !== false) {
                    echo "  ⭐ ⭐ ⭐ JOB FOR TEST-AUTOFIX PRODUCT! ⭐ ⭐ ⭐\n";
                }
            }
        }

        echo "\n";
    }
} else {
    echo "❌ BRAK JOBÓW W KOLEJCE!\n\n";
    echo "⚠️ PROBLEM: Joby są dispatchowane ale NIE TRAFIAJĄ do kolejki?\n";
    echo "⚠️ SPRAWDŹ: config/queue.php - connection setting\n";
    echo "⚠️ SPRAWDŹ: .env - QUEUE_CONNECTION\n";
}

// Check failed jobs
echo "\n=== FAILED JOBS ===\n";
$failedJobsTableExists = DB::select("SHOW TABLES LIKE 'failed_jobs'");

if (empty($failedJobsTableExists)) {
    echo "❌ Tabela 'failed_jobs' nie istnieje\n";
} else {
    $failedCount = DB::table('failed_jobs')->count();
    echo "Total: $failedCount failed jobs\n";

    if ($failedCount > 0) {
        $failed = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($failed as $f) {
            echo "\nFailed Job ID: {$f->id}\n";
            echo "  Connection: {$f->connection}\n";
            echo "  Queue: {$f->queue}\n";
            echo "  Failed At: {$f->failed_at}\n";
            echo "  Exception: " . substr($f->exception, 0, 200) . "...\n";
        }
    }
}

echo "\n";
