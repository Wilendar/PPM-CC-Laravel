<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductShopData;
use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\ProductTransformer;

echo "=== FAZA 5.2 FIX - TAX RATE SYNC TEST (4 SCENARIOS) ===" . PHP_EOL;
echo PHP_EOL;

// Test setup
$productId = 11033;
$shopId = 1; // B2B Test DEV

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product) {
    echo "❌ Product $productId not found" . PHP_EOL;
    exit(1);
}

if (!$shop) {
    echo "❌ Shop $shopId not found" . PHP_EOL;
    exit(1);
}

echo "Product: $productId ({$product->name})" . PHP_EOL;
echo "Shop: $shopId ({$shop->name})" . PHP_EOL;
echo "Product default tax_rate: {$product->tax_rate}%" . PHP_EOL;
echo PHP_EOL;

// Initialize ProductTransformer
$categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);
$priceGroupMapper = app(\App\Services\PrestaShop\PriceGroupMapper::class);
$warehouseMapper = app(\App\Services\PrestaShop\WarehouseMapper::class);
$transformer = new ProductTransformer($categoryMapper, $priceGroupMapper, $warehouseMapper);

// Create API client
$client = PrestaShopClientFactory::create($shop);

echo "=== SCENARIO 1: Default Tax Rate (no override) ===" . PHP_EOL;
echo PHP_EOL;

// Ensure no override
$psd = ProductShopData::where('product_id', $productId)->where('shop_id', $shopId)->first();
if ($psd) {
    $psd->update(['tax_rate_override' => null]);
    echo "✓ Cleared tax_rate_override (set to NULL)" . PHP_EOL;
} else {
    echo "ℹ No ProductShopData record (will use product default)" . PHP_EOL;
}

// Reload product to clear any cached relations
$product->refresh();

// Transform product
echo PHP_EOL;
echo "Transforming product..." . PHP_EOL;
$prestashopData = $transformer->transformForPrestaShop($product, $client);

echo "Result:" . PHP_EOL;
echo "  id_tax_rules_group: " . $prestashopData['product']['id_tax_rules_group'] . PHP_EOL;
echo "  Expected: 6 (for 23% VAT - Shop 1 config)" . PHP_EOL;
echo ($prestashopData['product']['id_tax_rules_group'] == 6 ? "✅ PASS" : "❌ FAIL") . PHP_EOL;
echo PHP_EOL;
echo str_repeat("-", 70) . PHP_EOL;
echo PHP_EOL;

// SCENARIO 2: Shop Override (8%)
echo "=== SCENARIO 2: Shop Override (8% VAT) ===" . PHP_EOL;
echo PHP_EOL;

$psd = ProductShopData::updateOrCreate(
    ['product_id' => $productId, 'shop_id' => $shopId],
    ['tax_rate_override' => 8.00]
);
echo "✓ Set tax_rate_override to 8.00" . PHP_EOL;

// Reload product
$product->refresh();

echo PHP_EOL;
echo "Transforming product..." . PHP_EOL;
$prestashopData = $transformer->transformForPrestaShop($product, $client);

echo "Result:" . PHP_EOL;
echo "  id_tax_rules_group: " . $prestashopData['product']['id_tax_rules_group'] . PHP_EOL;
echo "  Expected: 2 (for 8% VAT - Shop 1 config)" . PHP_EOL;
echo ($prestashopData['product']['id_tax_rules_group'] == 2 ? "✅ PASS" : "❌ FAIL") . PHP_EOL;
echo PHP_EOL;
echo str_repeat("-", 70) . PHP_EOL;
echo PHP_EOL;

// SCENARIO 3: Change Override (8% → 5%)
echo "=== SCENARIO 3: Change Override (8% → 5%) ===" . PHP_EOL;
echo PHP_EOL;

$psd->update(['tax_rate_override' => 5.00]);
echo "✓ Changed tax_rate_override from 8.00 to 5.00" . PHP_EOL;

// Reload product
$product->refresh();

echo PHP_EOL;
echo "Transforming product..." . PHP_EOL;
$prestashopData = $transformer->transformForPrestaShop($product, $client);

echo "Result:" . PHP_EOL;
echo "  id_tax_rules_group: " . $prestashopData['product']['id_tax_rules_group'] . PHP_EOL;
echo "  Expected: 3 (for 5% VAT - Shop 1 config)" . PHP_EOL;
echo ($prestashopData['product']['id_tax_rules_group'] == 3 ? "✅ PASS" : "❌ FAIL") . PHP_EOL;
echo PHP_EOL;
echo str_repeat("-", 70) . PHP_EOL;
echo PHP_EOL;

// SCENARIO 4: Clear Override (5% → inherit default 23%)
echo "=== SCENARIO 4: Clear Override (inherit default) ===" . PHP_EOL;
echo PHP_EOL;

$psd->update(['tax_rate_override' => null]);
echo "✓ Cleared tax_rate_override (set to NULL)" . PHP_EOL;

// Reload product
$product->refresh();

echo PHP_EOL;
echo "Transforming product..." . PHP_EOL;
$prestashopData = $transformer->transformForPrestaShop($product, $client);

echo "Result:" . PHP_EOL;
echo "  id_tax_rules_group: " . $prestashopData['product']['id_tax_rules_group'] . PHP_EOL;
echo "  Expected: 6 (for 23% VAT - Product default)" . PHP_EOL;
echo ($prestashopData['product']['id_tax_rules_group'] == 6 ? "✅ PASS" : "❌ FAIL") . PHP_EOL;
echo PHP_EOL;
echo str_repeat("-", 70) . PHP_EOL;
echo PHP_EOL;

echo "=== TEST COMPLETED ===" . PHP_EOL;
echo PHP_EOL;
echo "Check storage/logs/laravel.log for debug logs with '[FAZA 5.2 FIX]' marker" . PHP_EOL;
