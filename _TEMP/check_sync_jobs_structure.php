<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n=== STRUKTURA TABELI sync_jobs ===\n\n";

$columns = DB::select("SHOW COLUMNS FROM sync_jobs");

foreach ($columns as $col) {
    printf("%-30s %-20s %s\n",
        $col->Field,
        $col->Type,
        $col->Null == 'YES' ? 'NULL' : 'NOT NULL'
    );
}

echo "\n=== PRZYKŁADOWE REKORDY ===\n\n";

$jobs = DB::table('sync_jobs')
    ->orderBy('created_at', 'desc')
    ->limit(3)
    ->get();

foreach ($jobs as $job) {
    echo "Job ID: {$job->id}\n";
    echo "  Status: {$job->status}\n";
    echo "  Shop ID: {$job->prestashop_shop_id}\n";
    echo "  Batch ID: " . ($job->batch_id ?? 'NULL') . "\n";
    echo "  Created: {$job->created_at}\n";

    // Show all fields
    echo "  All fields: " . json_encode($job, JSON_PRETTY_PRINT) . "\n";
    echo "\n";
}

echo "\n";
