<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductScanResult;

// Check session 20 results
$results = ProductScanResult::where('scan_session_id', 20)
    ->selectRaw('match_status, count(*) as cnt')
    ->groupBy('match_status')
    ->get();

echo "Session 20 results:" . PHP_EOL;
foreach ($results as $r) {
    echo "  - {$r->match_status}: {$r->cnt}" . PHP_EOL;
}

// Show some already_linked results
echo PHP_EOL . "Already linked products:" . PHP_EOL;
$alreadyLinked = ProductScanResult::where('scan_session_id', 20)
    ->where('match_status', 'already_linked')
    ->limit(5)
    ->get(['sku', 'name', 'external_id', 'match_status', 'resolution_status']);

foreach ($alreadyLinked as $r) {
    echo "  SKU: {$r->sku}, External ID: {$r->external_id}, Status: {$r->match_status}" . PHP_EOL;
}
