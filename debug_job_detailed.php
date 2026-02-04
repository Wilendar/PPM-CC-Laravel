<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\ProductScanSession;
use App\Models\ProductScanResult;

echo "=== DETAILED JOB DEBUG ===" . PHP_EOL;

// Create session like the job does
$session = ProductScanSession::create([
    'scan_type' => ProductScanSession::SCAN_LINKS,
    'source_type' => 'baselinker',
    'source_id' => 1,
    'status' => ProductScanSession::STATUS_RUNNING,
    'user_id' => 8, // Admin user on production
]);

echo "Created session ID: " . $session->id . PHP_EOL;
echo "source_type: " . $session->source_type . PHP_EOL;
echo "source_id: " . $session->source_id . PHP_EOL;

// Check constant comparison
echo PHP_EOL . "=== CONSTANT CHECK ===" . PHP_EOL;
echo "SOURCE_PRESTASHOP = '" . ProductScanSession::SOURCE_PRESTASHOP . "'" . PHP_EOL;
echo "source_type = '" . $session->source_type . "'" . PHP_EOL;
echo "Is PrestaShop? " . ($session->source_type === ProductScanSession::SOURCE_PRESTASHOP ? 'YES' : 'NO') . PHP_EOL;

// Build query exactly like job does
$query = Product::query()
    ->whereNotNull('sku')
    ->where('sku', '!=', '')
    ->select(['id', 'sku', 'name', 'ean', 'manufacturer_id']);

if ($session->source_type === ProductScanSession::SOURCE_PRESTASHOP) {
    echo "Loading shopData relation" . PHP_EOL;
    $query->with([
        'shopData' => function ($q) use ($session) {
            $q->where('shop_id', $session->source_id);
        },
        'manufacturerRelation:id,name'
    ]);
} else {
    echo "Loading erpData relation" . PHP_EOL;
    $query->with([
        'erpData' => function ($q) use ($session) {
            $q->where('erp_connection_id', $session->source_id);
        },
        'manufacturerRelation:id,name'
    ]);
}

echo PHP_EOL . "=== PROCESS PRODUCTS ===" . PHP_EOL;

$stats = [
    'total' => 0,
    'already_linked' => 0,
    'matched' => 0,
    'unmatched' => 0,
];

// Process like the job does
$query->chunk(100, function ($products) use ($session, &$stats) {
    foreach ($products as $product) {
        $stats['total']++;

        // Check like isProductAlreadyLinked does
        if ($session->source_type === ProductScanSession::SOURCE_PRESTASHOP) {
            $isLinked = $product->shopData->isNotEmpty();
        } else {
            $isLinked = $product->erpData->isNotEmpty();
        }

        if ($isLinked) {
            $stats['already_linked']++;
            $matchStatus = ProductScanResult::MATCH_ALREADY_LINKED;
            $resolutionStatus = ProductScanResult::RESOLUTION_LINKED;

            // Get external_id
            $link = $product->erpData->first();
            $externalId = $link?->external_id;

            echo "  LINKED: {$product->sku} (external_id: {$externalId})" . PHP_EOL;
        } else {
            $stats['unmatched']++;
            $matchStatus = ProductScanResult::MATCH_UNMATCHED;
            $resolutionStatus = ProductScanResult::RESOLUTION_PENDING;
            $externalId = null;
        }

        // Create result like job does
        ProductScanResult::create([
            'scan_session_id' => $session->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'ppm_product_id' => $product->id,
            'external_source_type' => 'baselinker',
            'external_source_id' => 1,
            'external_id' => $externalId,
            'match_status' => $matchStatus,
            'resolution_status' => $resolutionStatus,
            'ppm_data' => [
                'id' => $product->id,
                'sku' => $product->sku,
                'name' => $product->name,
            ],
        ]);
    }
});

echo PHP_EOL . "=== STATS ===" . PHP_EOL;
echo "Total: " . $stats['total'] . PHP_EOL;
echo "Already linked: " . $stats['already_linked'] . PHP_EOL;
echo "Unmatched: " . $stats['unmatched'] . PHP_EOL;

// Verify results in DB
echo PHP_EOL . "=== VERIFY DB RESULTS ===" . PHP_EOL;
$results = ProductScanResult::where('scan_session_id', $session->id)
    ->selectRaw('match_status, count(*) as cnt')
    ->groupBy('match_status')
    ->get();

foreach ($results as $r) {
    echo "  - {$r->match_status}: {$r->cnt}" . PHP_EOL;
}

// Update session
$session->update([
    'status' => ProductScanSession::STATUS_COMPLETED,
    'total_scanned' => $stats['total'],
    'matched_count' => $stats['already_linked'],
    'unmatched_count' => $stats['unmatched'],
]);

echo PHP_EOL . "Session {$session->id} completed." . PHP_EOL;
