<?php
/**
 * Remove inline styles from PrestaShop product description
 *
 * Usage: php remove_inline_styles.php <product_id> <shop_id>
 * Example: php remove_inline_styles.php 4016 5
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShop8Client;

$productId = $argv[1] ?? null;
$shopId = $argv[2] ?? 5;

if (!$productId) {
    echo "Usage: php remove_inline_styles.php <prestashop_product_id> [shop_id]\n";
    exit(1);
}

echo "=== Remove Inline Styles from PrestaShop ===\n";
echo "Product ID: $productId\n";
echo "Shop ID: $shopId\n\n";

// Get shop
$shop = PrestaShopShop::find($shopId);
if (!$shop) {
    echo "ERROR: Shop not found\n";
    exit(1);
}

echo "Shop: {$shop->name}\n";
echo "URL: {$shop->url}\n\n";

// Create API client
$client = new PrestaShop8Client($shop);

// Fetch product
echo "Fetching product from PrestaShop...\n";
try {
    $product = $client->getProduct($productId);

    if (!$product) {
        echo "ERROR: Product not found\n";
        exit(1);
    }

    echo "Product: " . ($product['name'][0]['value'] ?? 'N/A') . "\n\n";

    // Get descriptions
    $descriptions = $product['description'] ?? [];
    $descriptionsShort = $product['description_short'] ?? [];

    $modified = false;

    // Process each language
    foreach ($descriptions as $i => $desc) {
        $langId = $desc['id'] ?? 1;
        $html = $desc['value'] ?? '';

        if (empty($html)) continue;

        // Check if has inline styles
        if (strpos($html, 'style="') !== false || strpos($html, "style='") !== false) {
            echo "Language $langId: Found inline styles\n";

            // Remove inline styles using regex
            $cleanHtml = preg_replace('/\s*style=["\'][^"\']*["\']/', '', $html);

            // Count removed
            $originalLength = strlen($html);
            $cleanLength = strlen($cleanHtml);
            $saved = $originalLength - $cleanLength;

            echo "  Original: $originalLength chars\n";
            echo "  Clean: $cleanLength chars\n";
            echo "  Saved: $saved chars (" . round($saved/$originalLength*100, 1) . "%)\n";

            $product['description'][$i]['value'] = $cleanHtml;
            $modified = true;
        } else {
            echo "Language $langId: No inline styles found\n";
        }
    }

    // Process short descriptions too
    foreach ($descriptionsShort as $i => $desc) {
        $langId = $desc['id'] ?? 1;
        $html = $desc['value'] ?? '';

        if (empty($html) || (strpos($html, 'style="') === false && strpos($html, "style='") === false)) {
            continue;
        }

        echo "Short description lang $langId: Found inline styles\n";
        $cleanHtml = preg_replace('/\s*style=["\'][^"\']*["\']/', '', $html);
        $product['description_short'][$i]['value'] = $cleanHtml;
        $modified = true;
    }

    if (!$modified) {
        echo "\nNo inline styles found - nothing to do\n";
        exit(0);
    }

    // Confirm before saving
    echo "\n";
    echo "Ready to update product on PrestaShop.\n";
    echo "Press ENTER to continue or Ctrl+C to cancel...\n";
    fgets(STDIN);

    // Update product
    echo "Updating product...\n";
    $result = $client->updateProduct($productId, [
        'description' => $product['description'],
        'description_short' => $product['description_short'],
    ]);

    if ($result) {
        echo "SUCCESS! Inline styles removed.\n";
    } else {
        echo "ERROR: Failed to update product\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\nDone!\n";
