<?php

/**
 * Test Product Transformer Output
 *
 * Verifies that ProductTransformer generates valid PrestaShop data
 * Checks all required fields and data format compliance
 *
 * FAZA 3B.3 - TEST 2: Transformer Output Verification
 *
 * Usage:
 *   php _TOOLS/test_product_transformer.php <product_id> [shop_id]
 *
 * Example:
 *   php _TOOLS/test_product_transformer.php 123
 *   php _TOOLS/test_product_transformer.php 123 2
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\ProductTransformer;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\Log;

echo "=== TEST PRODUCT TRANSFORMER ===\n\n";

// Parse arguments
$productId = $argv[1] ?? null;
$shopId = $argv[2] ?? 1;

if (!$productId) {
    echo "❌ ERROR: Product ID required\n";
    echo "Usage: php _TOOLS/test_product_transformer.php <product_id> [shop_id]\n";
    exit(1);
}

try {
    // Load product with relationships
    $product = Product::with([
        'categories',
        'prices',
        'stock',
        'shopData' => function ($query) use ($shopId) {
            $query->where('shop_id', $shopId);
        }
    ])->find($productId);

    if (!$product) {
        echo "❌ ERROR: Product not found (ID: {$productId})\n";
        exit(1);
    }

    echo "✅ Product loaded:\n";
    echo "  ID: {$product->id}\n";
    echo "  SKU: {$product->sku}\n";
    echo "  Name: {$product->name}\n\n";

    // Load shop
    $shop = PrestaShopShop::find($shopId);
    if (!$shop) {
        echo "❌ ERROR: Shop not found (ID: {$shopId})\n";
        exit(1);
    }

    echo "✅ Shop loaded:\n";
    echo "  ID: {$shop->id}\n";
    echo "  Name: {$shop->name}\n";
    echo "  Version: {$shop->version}\n\n";

    // Create PrestaShop client
    $clientFactory = app(PrestaShopClientFactory::class);
    $client = $clientFactory->create($shop);

    echo "✅ PrestaShop client created (version {$client->getVersion()})\n\n";

    // Transform product
    echo "Transforming product...\n";
    $transformer = app(ProductTransformer::class);
    $transformed = $transformer->transformForPrestaShop($product, $client);

    echo "✅ Transformation completed\n\n";

    // Display transformed data
    echo "=== TRANSFORMED DATA ===\n";
    echo json_encode($transformed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // Verify critical fields
    echo "=== FIELD VERIFICATION ===\n";

    $productData = $transformed['product'] ?? [];

    $checks = [
        'reference' => [
            'value' => $productData['reference'] ?? null,
            'expected' => $product->sku,
            'required' => true,
        ],
        'name' => [
            'value' => $productData['name'] ?? null,
            'expected' => 'multilingual array',
            'required' => true,
        ],
        'price' => [
            'value' => $productData['price'] ?? null,
            'expected' => 'float > 0',
            'required' => true,
        ],
        'active' => [
            'value' => $productData['active'] ?? null,
            'expected' => '0 or 1',
            'required' => true,
        ],
        'weight' => [
            'value' => $productData['weight'] ?? null,
            'expected' => 'float',
            'required' => false,
        ],
        'description' => [
            'value' => $productData['description'] ?? null,
            'expected' => 'multilingual array',
            'required' => false,
        ],
        'description_short' => [
            'value' => $productData['description_short'] ?? null,
            'expected' => 'multilingual array',
            'required' => false,
        ],
        'associations.categories' => [
            'value' => $productData['associations']['categories'] ?? null,
            'expected' => 'array of category IDs',
            'required' => true,
        ],
    ];

    $errors = [];
    $warnings = [];

    foreach ($checks as $field => $check) {
        $value = $check['value'];
        $status = '❓';

        if ($value === null) {
            if ($check['required']) {
                $status = '❌';
                $errors[] = "{$field}: MISSING (required)";
            } else {
                $status = '⚠️';
                $warnings[] = "{$field}: Missing (optional)";
            }
        } else {
            $status = '✅';
        }

        echo "{$status} {$field}: ";

        if (is_array($value)) {
            echo "Array(" . count($value) . " items)";
            if ($field === 'name' || $field === 'description' || $field === 'description_short') {
                // Verify multilingual structure
                if (isset($value[0]['id']) && isset($value[0]['value'])) {
                    echo " - Valid multilingual format";
                } else {
                    $status = '❌';
                    $errors[] = "{$field}: Invalid multilingual format (expected [{'id': X, 'value': 'text'}])";
                }
            }
        } elseif (is_numeric($value)) {
            echo "{$value}";
        } elseif (is_string($value)) {
            echo "\"{$value}\"";
        } else {
            echo gettype($value);
        }

        echo " (expected: {$check['expected']})\n";
    }

    echo "\n";

    // Check required PrestaShop fields
    echo "=== PRESTASHOP REQUIRED FIELDS CHECK ===\n";

    $requiredFields = [
        'reference',      // SKU
        'price',          // Price
        'name',           // Product name (multilingual)
        'active',         // Active status
    ];

    $missingRequired = [];
    foreach ($requiredFields as $field) {
        if (empty($productData[$field])) {
            $missingRequired[] = $field;
            echo "❌ {$field}: MISSING\n";
        } else {
            echo "✅ {$field}: Present\n";
        }
    }

    echo "\n";

    // Summary
    echo "=== VERIFICATION SUMMARY ===\n";

    if (!empty($errors)) {
        echo "❌ ERRORS FOUND (" . count($errors) . "):\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
        echo "\n";
    }

    if (!empty($warnings)) {
        echo "⚠️ WARNINGS (" . count($warnings) . "):\n";
        foreach ($warnings as $warning) {
            echo "  - {$warning}\n";
        }
        echo "\n";
    }

    if (empty($errors) && empty($missingRequired)) {
        echo "✅ All required fields present\n";
        echo "✅ Transformer output is valid for PrestaShop API\n";
        echo "\nREADY FOR SYNC!\n";
    } else {
        echo "❌ Transformer output has issues - FIX REQUIRED\n";
        exit(1);
    }

} catch (\Exception $e) {
    echo "\n❌ ERROR: Transformer test failed\n";
    echo "Message: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    echo "\nStack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
