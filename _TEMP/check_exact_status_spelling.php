<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== CHECKING EXACT STATUS SPELLING ===\n\n";

$jobs = DB::table('sync_jobs')
    ->select('id', 'status', 'created_at')
    ->whereIn('status', ['canceled', 'cancelled'])
    ->get();

echo "Jobs with 'cancel*' status:\n";
foreach ($jobs as $job) {
    printf("  ID %d: status='%s' (length=%d) created=%s\n",
        $job->id,
        $job->status,
        strlen($job->status),
        $job->created_at
    );
}

echo "\n=== ENUM VALUES ===\n";
$result = DB::select("SHOW COLUMNS FROM sync_jobs WHERE Field = 'status'");
if ($result) {
    echo "Type: " . $result[0]->Type . "\n";
}

echo "\n";
