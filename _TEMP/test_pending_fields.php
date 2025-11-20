<?php
// Test Pending Fields Feature
// 2025-11-07

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find first product with shop data
$productShopData = \App\Models\ProductShopData::first();

if (!$productShopData) {
    echo "No product_shop_data found!\n";
    exit(1);
}

echo "=== TEST: Pending Fields Feature ===\n\n";

echo "Product ID: {$productShopData->product_id}\n";
echo "Shop ID: {$productShopData->shop_id}\n";
echo "Current sync_status: {$productShopData->sync_status}\n";
echo "Current pending_fields: " . json_encode($productShopData->pending_fields) . "\n\n";

// Set test pending_fields
$productShopData->update([
    'sync_status' => 'pending',
    'pending_fields' => ['nazwa', 'cena', 'waga']
]);

echo "✅ Updated with test pending_fields: ['nazwa', 'cena', 'waga']\n\n";

// Reload and verify
$productShopData->refresh();
echo "Verification:\n";
echo "  sync_status: {$productShopData->sync_status}\n";
echo "  pending_fields: " . json_encode($productShopData->pending_fields) . "\n";
echo "  pending_fields is array: " . (is_array($productShopData->pending_fields) ? 'YES' : 'NO') . "\n\n";

// Test display format
if (!empty($productShopData->pending_fields)) {
    $displayText = 'Oczekuje: ' . implode(', ', $productShopData->pending_fields);
    echo "Display format: {$displayText}\n";
} else {
    echo "Display format: Oczekuje\n";
}

echo "\n✅ TEST COMPLETE - Product ID: {$productShopData->product_id}\n";
echo "Open URL: https://ppm.mpptrade.pl/admin/products/{$productShopData->product_id}/edit\n";
