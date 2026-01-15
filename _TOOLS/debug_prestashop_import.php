<?php

/**
 * Debug PrestaShop description import
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrestaShopShop;
use App\Models\Product;
use App\Services\PrestaShop\PrestaShop8Client;
use App\Services\VisualEditor\HtmlToBlocksParser;

$shop = PrestaShopShop::find(5);
$product = Product::where('sku', 'BG-KAYO-S200')->first();

echo "=== SHOP ===\n";
echo "ID: {$shop->id}, Name: {$shop->name}\n\n";

echo "=== PPM PRODUCT ===\n";
echo "ID: {$product->id}, SKU: {$product->sku}\n";

// Get PrestaShop product ID
$psProductId = $product->getPrestashopProductId($shop);
echo "PrestaShop Product ID: " . ($psProductId ?? 'NULL') . "\n\n";

if (!$psProductId) {
    echo "ERROR: Product not synced to PrestaShop\n";
    exit(1);
}

// Fetch from PrestaShop API
$client = new PrestaShop8Client($shop);
$psProductRaw = $client->getProduct($psProductId);

echo "=== RAW API RESPONSE KEYS ===\n";
print_r(array_keys($psProductRaw));

// Check if wrapped in 'product'
$psProduct = $psProductRaw['product'] ?? $psProductRaw;

echo "\n=== PRODUCT DATA KEYS ===\n";
print_r(array_keys($psProduct));

echo "\n=== DESCRIPTION FIELD ===\n";
echo "Type: " . gettype($psProduct['description'] ?? null) . "\n";

if (isset($psProduct['description'])) {
    if (is_array($psProduct['description'])) {
        echo "Is array with " . count($psProduct['description']) . " elements\n";

        // Check if it's language array
        if (isset($psProduct['description']['language'])) {
            echo "Has 'language' key\n";
            print_r($psProduct['description']['language']);
        } else {
            // Direct array
            foreach ($psProduct['description'] as $idx => $item) {
                if (is_array($item)) {
                    echo "Item {$idx}: ";
                    print_r($item);
                } else {
                    echo "Item {$idx}: " . substr($item, 0, 100) . "\n";
                }
            }
        }
    } else {
        echo "Value: " . substr($psProduct['description'], 0, 500) . "\n";
    }
}

// Try to extract description
function extractDescription($field) {
    if ($field === null) return null;
    if (is_string($field)) return $field;

    if (is_array($field)) {
        // Check for 'language' wrapper (common in PrestaShop API)
        if (isset($field['language'])) {
            $langArray = $field['language'];
            if (is_array($langArray)) {
                // Check if it's a single language (not wrapped in array)
                if (isset($langArray['value'])) {
                    return $langArray['value'];
                }
                // Multiple languages
                foreach ($langArray as $lang) {
                    if (isset($lang['value']) && !empty($lang['value'])) {
                        return $lang['value'];
                    }
                }
            }
        }

        // Direct array format
        foreach ($field as $item) {
            if (is_array($item) && isset($item['value']) && !empty($item['value'])) {
                return $item['value'];
            }
            if (is_string($item) && !empty($item)) {
                return $item;
            }
        }
    }

    return null;
}

$description = extractDescription($psProduct['description'] ?? null);

echo "\n=== EXTRACTED DESCRIPTION ===\n";
echo "Length: " . strlen($description ?? '') . " characters\n";
echo "First 2000 chars:\n";
echo substr($description ?? 'EMPTY', 0, 2000) . "\n";

if (!empty($description)) {
    echo "\n=== PARSING WITH HtmlToBlocksParser ===\n";
    $parser = new HtmlToBlocksParser();
    $blocks = $parser->parse($description);

    echo "Blocks count: " . count($blocks) . "\n";
    echo "Block types:\n";
    foreach ($blocks as $idx => $block) {
        $type = $block['type'] ?? 'unknown';
        $dataKeys = array_keys($block['data'] ?? []);
        echo "  [{$idx}] {$type} - data keys: " . implode(', ', $dataKeys) . "\n";
    }
}
