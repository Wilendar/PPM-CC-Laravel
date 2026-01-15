<?php
/**
 * Check FULL sync flow: PPM -> ProductTransformer -> PrestaShop API
 * Verify what actually gets sent to PrestaShop
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\ProductTransformer;
use App\Services\PrestaShop\PrestaShop8Client;
use App\Services\PrestaShop\CategoryMapper;
use App\Services\PrestaShop\PriceGroupMapper;
use App\Services\PrestaShop\WarehouseMapper;

$productId = 11183;
$shopId = 5;

echo "=== FULL SYNC FLOW CHECK ===\n";
echo "Product ID: {$productId}\n";
echo "Shop ID: {$shopId}\n\n";

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product || !$shop) {
    die("Product or Shop not found!\n");
}

// Step 1: Check ProductDescription in DB
echo "=== STEP 1: ProductDescription DB Record ===\n";
$desc = ProductDescription::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$desc) {
    die("ProductDescription not found!\n");
}

echo "sync_to_prestashop: " . ($desc->sync_to_prestashop ? 'TRUE' : 'FALSE') . "\n";
echo "target_field: " . ($desc->target_field ?? 'NULL') . "\n";
echo "css_mode: " . ($desc->css_mode ?? 'NULL') . "\n";

$renderedHtml = $desc->rendered_html ?? '';
$hasUveClass = strpos($renderedHtml, 'uve-') !== false;
echo "rendered_html has UVE class: " . ($hasUveClass ? "YES" : "NO") . "\n";

if ($hasUveClass) {
    preg_match_all('/uve-[se][a-f0-9]+/', $renderedHtml, $uveMatches);
    echo "UVE classes found: " . implode(', ', array_unique($uveMatches[0])) . "\n";
}

// Step 2: Check getHtmlForPrestaShop() output
echo "\n=== STEP 2: getHtmlForPrestaShop() Output ===\n";
$htmlData = $desc->getHtmlForPrestaShop();
$descriptionHtml = $htmlData['description'] ?? '';
echo "Returns description field: " . (!empty($descriptionHtml) ? "YES (" . strlen($descriptionHtml) . " chars)" : "NO/EMPTY") . "\n";
echo "description has UVE class: " . (strpos($descriptionHtml, 'uve-') !== false ? "YES" : "NO") . "\n";

// Step 3: Check getVisualDescription() method using container
echo "\n=== STEP 3: ProductTransformer::getVisualDescription() ===\n";
try {
    // Resolve transformer from container (with all dependencies)
    $transformer = app(ProductTransformer::class);

    // Use reflection to call protected method
    $reflection = new ReflectionClass($transformer);
    $method = $reflection->getMethod('getVisualDescription');
    $method->setAccessible(true);

    $visualDesc = $method->invoke($transformer, $product, $shop, 'description');
    echo "Visual description returned: " . (!empty($visualDesc) ? "YES (" . strlen($visualDesc) . " chars)" : "NO/EMPTY") . "\n";

    if (!empty($visualDesc)) {
        echo "Has UVE class: " . (strpos($visualDesc, 'uve-') !== false ? "YES" : "NO") . "\n";
        echo "Has uve-sa5bca8e8: " . (strpos($visualDesc, 'uve-sa5bca8e8') !== false ? "YES" : "NO") . "\n";
        echo "\nFirst 400 chars:\n" . substr($visualDesc, 0, 400) . "\n...\n";
    }
} catch (Exception $e) {
    echo "Error calling getVisualDescription: " . $e->getMessage() . "\n";
}

// Step 4: Check transformForPrestaShop() full output
echo "\n=== STEP 4: transformForPrestaShop() Full Output ===\n";
try {
    $transformer = app(ProductTransformer::class);

    $psData = $transformer->transformForPrestaShop($product, $shop);

    // Get description from transformed data
    $transformedDesc = $psData['description'] ?? null;

    if (is_array($transformedDesc)) {
        // Multilang field - get first value
        $descValue = reset($transformedDesc);
        $langId = key($transformedDesc);
        echo "description is multilang array, lang_id: {$langId}\n";
    } else {
        $descValue = $transformedDesc;
    }

    echo "Transformed description length: " . strlen($descValue ?? '') . " chars\n";
    echo "Has UVE class: " . (strpos($descValue ?? '', 'uve-') !== false ? "YES" : "NO") . "\n";
    echo "Has uve-sa5bca8e8: " . (strpos($descValue ?? '', 'uve-sa5bca8e8') !== false ? "YES" : "NO") . "\n";

    if (!empty($descValue)) {
        echo "\nFirst 500 chars of transformed description:\n";
        echo substr($descValue, 0, 500) . "\n";
    }

} catch (Exception $e) {
    echo "Error in transformForPrestaShop: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Step 5: Compare with what's currently on PrestaShop
echo "\n=== STEP 5: Current PrestaShop Data ===\n";
try {
    $client = new PrestaShop8Client($shop);
    $psProductId = $product->getPrestaShopId($shopId);

    echo "PrestaShop product ID: " . ($psProductId ?? 'NOT FOUND') . "\n";

    if ($psProductId) {
        $psProduct = $client->getProduct($psProductId);

        // Handle different response formats
        $psDescription = null;
        if (isset($psProduct['product']['description']['language'])) {
            // Multilang format
            $langs = $psProduct['product']['description']['language'];
            if (isset($langs[0]['value'])) {
                $psDescription = $langs[0]['value'];
            } elseif (isset($langs['value'])) {
                $psDescription = $langs['value'];
            }
        } elseif (isset($psProduct['product']['description'])) {
            $psDescription = $psProduct['product']['description'];
        }

        if ($psDescription) {
            echo "PS description length: " . strlen($psDescription) . " chars\n";
            echo "PS has UVE class: " . (strpos($psDescription, 'uve-') !== false ? "YES" : "NO") . "\n";
            echo "PS has uve-sa5bca8e8: " . (strpos($psDescription, 'uve-sa5bca8e8') !== false ? "YES" : "NO") . "\n";

            echo "\nPS description first 500 chars:\n";
            echo substr($psDescription, 0, 500) . "\n";
        } else {
            echo "Could not extract description from PS response\n";
        }
    }
} catch (Exception $e) {
    echo "Error fetching PrestaShop data: " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNOSIS ===\n";
echo "If STEP 1-4 show YES for UVE classes but STEP 5 shows NO:\n";
echo "  -> Sync job hasn't run yet OR sync doesn't use transformed data properly\n";
echo "If STEP 3 or 4 show NO for UVE classes:\n";
echo "  -> Bug in getVisualDescription() or transformForPrestaShop() priority logic\n";
