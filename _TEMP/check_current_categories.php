<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductShopData;

echo "=== CHECKING PRODUCT 11034 CATEGORIES IN DATABASE ===\n\n";

$psd = ProductShopData::where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if (!$psd) {
    die("ProductShopData NOT FOUND!\n");
}

$cm = $psd->category_mappings;

echo "CATEGORY MAPPINGS:\n";
echo json_encode($cm, JSON_PRETTY_PRINT) . "\n\n";

echo "SELECTED CATEGORIES: " . json_encode($cm['ui']['selected'] ?? []) . "\n";
echo "PRIMARY CATEGORY: " . ($cm['ui']['primary'] ?? 'NULL') . "\n";
echo "MAPPINGS COUNT: " . count($cm['mappings'] ?? []) . "\n\n";

echo "Updated at: {$psd->updated_at}\n";

echo "\n=== COMPLETE ===\n";
