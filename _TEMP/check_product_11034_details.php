<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShop8Client;

echo "=== PRODUCT 11034 DIAGNOSTIC ===\n\n";

// 1. Get ProductShopData
$psd = ProductShopData::where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if (!$psd) {
    echo "ERROR: ProductShopData NOT FOUND\n";
    exit(1);
}

echo "STEP 1: PPM DATABASE STATE\n";
echo "  Product ID: {$psd->product_id}\n";
echo "  Shop ID: {$psd->shop_id}\n";
echo "  PrestaShop Product ID: {$psd->prestashop_product_id}\n";
echo "  SKU: {$psd->product->sku}\n";
echo "  Shop: {$psd->shop->name}\n";
echo "  Sync Status: {$psd->sync_status}\n\n";

echo "  Category Mappings (from DB):\n";
$cm = $psd->category_mappings;
$selected = $cm['ui']['selected'] ?? [];
$primary = $cm['ui']['primary'] ?? null;
echo "    Selected: " . json_encode($selected) . "\n";
echo "    Primary: " . ($primary ?? 'NULL') . "\n";
echo "    Count: " . count($selected) . "\n\n";

// 2. Fetch from PrestaShop API
echo "STEP 2: PRESTASHOP API RESPONSE\n";

try {
    $shop = $psd->shop;
    $client = new PrestaShop8Client(
        $shop->api_url,
        decrypt($shop->api_key)
    );

    $psProductId = $psd->prestashop_product_id;
    echo "  Fetching product {$psProductId} from PrestaShop...\n";

    $response = $client->getProduct($psProductId);

    if (isset($response['product']['associations']['categories']['category'])) {
        $categories = $response['product']['associations']['categories']['category'];

        // Normalize to array
        if (isset($categories['id'])) {
            $categories = [$categories];
        }

        echo "  PrestaShop Categories:\n";
        foreach ($categories as $cat) {
            echo "    PrestaShop Category ID: {$cat['id']}\n";
        }
        echo "  Total: " . count($categories) . "\n\n";

        // 3. Map PrestaShop IDs to PPM IDs
        echo "STEP 3: CATEGORY MAPPING (PrestaShop -> PPM)\n";

        $categoryMapper = app(\App\Services\PrestaShop\CategoryMapper::class);

        foreach ($categories as $cat) {
            $psCatId = $cat['id'];
            $ppmCatId = $categoryMapper->mapPrestaShopToPPM($shop->id, $psCatId);

            echo "    PrestaShop {$psCatId} -> PPM {$ppmCatId}\n";

            // Check if PPM category exists
            $ppmCat = \App\Models\Category::find($ppmCatId);
            if ($ppmCat) {
                echo "      PPM Category: {$ppmCat->name}\n";
            } else {
                echo "      ERROR: PPM Category {$ppmCatId} DOES NOT EXIST (ghost)\n";
            }
        }

    } else {
        echo "  ERROR: No categories found in PrestaShop response\n";
    }

} catch (\Exception $e) {
    echo "  ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
