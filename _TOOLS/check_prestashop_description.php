<?php
/**
 * Check ONLY PrestaShop description - does it have UVE classes?
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Services\PrestaShop\PrestaShop8Client;

$productId = 11183;
$shopId = 5;

echo "=== PRESTASHOP DESCRIPTION CHECK ===\n";
echo "Product ID: {$productId}\n";
echo "Shop ID: {$shopId}\n\n";

$product = Product::find($productId);
$shop = PrestaShopShop::find($shopId);

if (!$product || !$shop) {
    die("Product or Shop not found!\n");
}

// Get PrestaShop product ID from ProductShopData
$shopData = ProductShopData::where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

$psProductId = $shopData?->prestashop_product_id;

echo "PrestaShop product ID: " . ($psProductId ?? 'NOT FOUND') . "\n\n";

if (!$psProductId) {
    die("Product not synced to this shop!\n");
}

try {
    $client = new PrestaShop8Client($shop);
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
        echo "=== RESULTS ===\n";
        echo "PS description length: " . strlen($psDescription) . " chars\n";
        echo "PS has ANY UVE class (uve-): " . (strpos($psDescription, 'uve-') !== false ? "YES" : "NO") . "\n";
        echo "PS has uve-sa5bca8e8 (Buggy underline): " . (strpos($psDescription, 'uve-sa5bca8e8') !== false ? "YES" : "NO") . "\n";
        echo "PS has uve-sd2620bf6: " . (strpos($psDescription, 'uve-sd2620bf6') !== false ? "YES" : "NO") . "\n";
        echo "PS has uve-e73457325: " . (strpos($psDescription, 'uve-e73457325') !== false ? "YES" : "NO") . "\n";

        // Extract the Buggy span to verify
        if (preg_match('/<span[^>]*class="[^"]*uve-sa5bca8e8[^"]*"[^>]*>[^<]*<\/span>/', $psDescription, $match)) {
            echo "\n=== BUGGY SPAN HTML (with UVE class) ===\n";
            echo $match[0] . "\n";
        } elseif (preg_match('/<span[^>]*data-uve-id="block-0-span-3"[^>]*>[^<]*<\/span>/', $psDescription, $match)) {
            echo "\n=== BUGGY SPAN HTML (without UVE class) ===\n";
            echo $match[0] . "\n";
        }

        echo "\n=== FIRST 600 CHARS OF PS DESCRIPTION ===\n";
        echo substr($psDescription, 0, 600) . "\n";
    } else {
        echo "Could not extract description from PS response\n";
    }
} catch (Exception $e) {
    echo "Error fetching PrestaShop data: " . $e->getMessage() . "\n";
}
