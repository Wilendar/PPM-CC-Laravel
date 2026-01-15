<?php
/**
 * Test checksum fix - verify visual description is included in checksum
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Services\PrestaShop\Sync\ProductSyncStrategy;

$productId = 11183;
$shopId = 5;

echo "=== CHECKSUM FIX TEST ===\n";
echo "Product ID: {$productId}\n";
echo "Shop ID: {$shopId}\n\n";

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product || !$shop) {
    die("Product or Shop not found!\n");
}

// Get current sync status
$syncStatus = ProductShopData::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

echo "=== CURRENT SYNC STATUS ===\n";
echo "Stored checksum: " . ($syncStatus->checksum ?? 'NULL') . "\n\n";

// Calculate new checksum
$strategy = app(ProductSyncStrategy::class);
$newChecksum = $strategy->calculateChecksum($product, $shop);

echo "=== NEW CHECKSUM ===\n";
echo "Calculated checksum: {$newChecksum}\n";
echo "Checksums match: " . ($newChecksum === ($syncStatus->checksum ?? '')) ? "YES (no sync needed)" : "NO (sync WILL happen)" . "\n\n";

// Show what's included in visual description hash
$visualDesc = ProductDescription::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->where('sync_to_prestashop', true)
    ->first();

if ($visualDesc) {
    echo "=== VISUAL DESCRIPTION HASH COMPONENTS ===\n";
    echo "rendered_html hash: " . md5($visualDesc->rendered_html ?? '') . "\n";
    echo "css_rules hash: " . md5(json_encode($visualDesc->css_rules ?? [])) . "\n";
    echo "css_rules count: " . count($visualDesc->css_rules ?? []) . "\n";

    // Show css_rules keys (selectors)
    if (!empty($visualDesc->css_rules)) {
        echo "\nCSS Rules selectors:\n";
        foreach ($visualDesc->css_rules as $selector => $rules) {
            echo "  {$selector}\n";
        }
    }
} else {
    echo "NO visual description with sync_to_prestashop=true found!\n";
}

echo "\n=== NEXT SYNC WILL ===\n";
if ($newChecksum === ($syncStatus->checksum ?? '')) {
    echo "SKIP - checksums match\n";
} else {
    echo "EXECUTE - checksums DIFFER\n";
    echo "This means visual description changes are now detected!\n";
}
