<?php
/**
 * Check what ProductTransformer returns for description
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\ProductTransformer;

$productId = 11183;
$shopId = 5;

echo "=== TRANSFORMER OUTPUT CHECK ===\n";
echo "Product ID: {$productId}\n";
echo "Shop ID: {$shopId}\n\n";

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product || !$shop) {
    die("Product or Shop not found!\n");
}

// Check ProductDescription
$desc = ProductDescription::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

echo "=== PRODUCT DESCRIPTION RECORD ===\n";
if ($desc) {
    echo "sync_to_prestashop: " . ($desc->sync_to_prestashop ? 'TRUE' : 'FALSE') . "\n";
    echo "target_field: " . ($desc->target_field ?? 'NULL') . "\n";
    echo "rendered_html has UVE class: " . (strpos($desc->rendered_html ?? '', 'uve-sa5bca8e8') !== false ? 'YES' : 'NO') . "\n";

    // Call getHtmlForPrestaShop
    $htmlData = $desc->getHtmlForPrestaShop();
    echo "\ngetHtmlForPrestaShop()['description'] has UVE class: " .
        (strpos($htmlData['description'] ?? '', 'uve-sa5bca8e8') !== false ? 'YES' : 'NO') . "\n";
} else {
    echo "NO ProductDescription record found!\n";
}

// Check transformer output
echo "\n=== TRANSFORMER OUTPUT ===\n";
try {
    $transformer = app(ProductTransformer::class);
    $psData = $transformer->transformForPrestaShop($product, $shop);

    $descriptionField = $psData['description'] ?? 'NULL';
    if (is_array($descriptionField)) {
        $descriptionField = json_encode($descriptionField);
    }

    // Extract multilang field (usually id_lang => text)
    $descText = is_array($psData['description'])
        ? reset($psData['description'])
        : $psData['description'];

    echo "description field has UVE class: " .
        (strpos($descText ?? '', 'uve-sa5bca8e8') !== false ? 'YES' : 'NO') . "\n";

    echo "description first 500 chars:\n";
    echo substr($descText ?? '', 0, 500) . "\n";

} catch (Exception $e) {
    echo "Transformer error: " . $e->getMessage() . "\n";
}
