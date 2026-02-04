<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductScanSession;
use App\Jobs\Scan\ScanProductLinksJob;

// Create scan session
$session = ProductScanSession::create([
    'scan_type' => ProductScanSession::SCAN_LINKS,
    'source_type' => 'baselinker',
    'source_id' => 1,
    'status' => ProductScanSession::STATUS_PENDING,
    'user_id' => 8,
]);

echo "Created session ID: " . $session->id . PHP_EOL;

// Run job synchronously
try {
    ScanProductLinksJob::dispatchSync($session->id, 'baselinker', 1);

    // Refresh and show results
    $session->refresh();
    echo "Status: " . $session->status . PHP_EOL;
    echo "Total scanned: " . $session->total_scanned . PHP_EOL;
    echo "Matched: " . $session->matched_count . PHP_EOL;
    echo "Unmatched: " . $session->unmatched_count . PHP_EOL;

    // Check match statuses in results
    $results = \App\Models\ProductScanResult::where('scan_session_id', $session->id)
        ->selectRaw('match_status, count(*) as cnt')
        ->groupBy('match_status')
        ->get();

    echo "Results by status:" . PHP_EOL;
    foreach ($results as $r) {
        echo "  - " . $r->match_status . ": " . $r->cnt . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
