<?php
/**
 * ETAP_07h: Force re-render and sync product description with CSS classes
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductDescription;
use App\Services\PrestaShop\ProductTransformer;
use Illuminate\Support\Facades\Log;

$productId = $argv[1] ?? 11183;
$shopId = $argv[2] ?? 5;

echo "=== FORCE RE-RENDER AND SYNC ===\n";
echo "Product ID: {$productId}\n";
echo "Shop ID: {$shopId}\n\n";

// Find the description
$desc = ProductDescription::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$desc) {
    die("ProductDescription not found!\n");
}

echo "=== BEFORE RE-RENDER ===\n";
echo "css_class_map count: " . count($desc->css_class_map ?? []) . "\n";
echo "css_class_map: " . json_encode($desc->css_class_map, JSON_PRETTY_PRINT) . "\n\n";

// Check current rendered_html for CSS classes
$currentHtml = $desc->rendered_html ?? '';
$hasUveClasses = preg_match('/class="[^"]*uve-[es][a-f0-9]+/', $currentHtml);
echo "Current HTML has UVE classes: " . ($hasUveClasses ? "YES" : "NO") . "\n\n";

// Force re-render by clearing cache
echo "Clearing rendered_html cache...\n";
$desc->rendered_html = null;
$desc->last_rendered_at = null;
$desc->saveQuietly();

// Re-render with CSS class map
echo "Re-rendering with CSS class map...\n";
$newHtml = $desc->renderAndCache();

echo "\n=== AFTER RE-RENDER ===\n";
echo "New HTML length: " . strlen($newHtml) . " chars\n";

// Check if UVE classes are in the new HTML
$cssClassMap = $desc->css_class_map ?? [];
$foundClasses = 0;
foreach ($cssClassMap as $elementId => $cssClass) {
    if (str_contains($newHtml, 'class="' . $cssClass) || str_contains($newHtml, ' ' . $cssClass)) {
        echo "FOUND class: {$cssClass} (for {$elementId})\n";
        $foundClasses++;
    } else {
        echo "MISSING class: {$cssClass} (for {$elementId})\n";
    }
}

echo "\nTotal classes found: {$foundClasses} / " . count($cssClassMap) . "\n";

// Show sample of HTML with data-uve-id
if (preg_match_all('/data-uve-id="([^"]+)"/', $newHtml, $matches)) {
    echo "\n=== DATA-UVE-ID ELEMENTS ===\n";
    foreach (array_unique($matches[1]) as $id) {
        // Get the element with this id
        if (preg_match('/<[^>]*data-uve-id="' . preg_quote($id) . '"[^>]*>/i', $newHtml, $elemMatch)) {
            echo "Element with id={$id}:\n";
            echo "  " . substr($elemMatch[0], 0, 150) . (strlen($elemMatch[0]) > 150 ? '...' : '') . "\n";
        }
    }
}

echo "\n=== SYNC TO PRESTASHOP ===\n";
// Get the HTML for PrestaShop
$htmlData = $desc->getHtmlForPrestaShop();
echo "Description length: " . strlen($htmlData['description'] ?? '') . " chars\n";

// Now trigger the actual PrestaShop sync
$product = $desc->product;
if ($product) {
    try {
        $shop = App\Models\PrestaShopShop::find($shopId);
        if ($shop) {
            $client = app(App\Services\PrestaShop\PrestaShop8Client::class);
            $client->setShop($shop);

            // Get product data from PrestaShop
            $psProduct = $client->getProduct($product->prestashop_id ?? 0);
            if ($psProduct) {
                // Update only description
                $updateData = [
                    'description' => [
                        $shop->language_id ?? 1 => $htmlData['description']
                    ]
                ];

                $client->updateProduct($psProduct['id'], $updateData);
                echo "Product synced to PrestaShop!\n";
            } else {
                echo "Product not found in PrestaShop (ps_id: " . ($product->prestashop_id ?? 'null') . ")\n";
            }
        }
    } catch (Exception $e) {
        echo "Sync error: " . $e->getMessage() . "\n";
    }
}

echo "\nDone!\n";
