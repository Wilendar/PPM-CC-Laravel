<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "\n=== SYNC_JOBS TABLE STRUCTURE ===\n\n";

$columns = Schema::getColumnListing('sync_jobs');

echo "Total columns: " . count($columns) . "\n\n";

echo "All columns:\n";
foreach ($columns as $col) {
    echo "  - {$col}\n";
}

echo "\n";
