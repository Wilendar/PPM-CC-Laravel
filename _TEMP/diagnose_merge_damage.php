<?php
// Diagnose what happened to PB-KAYO-E-KMB and Q-KAYO-EA70 after update

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use App\Services\PrestaShop\ProductTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== DIAGNOSE UPDATE DAMAGE FOR SPECIFIC PRODUCTS ===\n\n";

// Test products
$testSKUs = ['PB-KAYO-E-KMB', 'Q-KAYO-EA70'];

// Get shop
$shop = PrestaShopShop::where('name', 'B2B Test DEV')->first();

if (!$shop) {
    echo "❌ Shop 'B2B Test DEV' not found\n";
    exit(1);
}

echo "Shop: {$shop->name} (ID: {$shop->id})\n";
echo "PrestaShop Shop ID: {$shop->prestashop_shop_id}\n\n";

// Create PrestaShop API client
$client = PrestaShopClientFactory::create($shop);

foreach ($testSKUs as $sku) {
    echo str_repeat('=', 100) . "\n";
    echo "PRODUCT: {$sku}\n";
    echo str_repeat('=', 100) . "\n\n";

    // 1. Find in PPM
    $product = Product::where('sku', $sku)->first();

    if (!$product) {
        echo "❌ Product not found in PPM database\n\n";
        continue;
    }

    echo "✅ PPM Product Found:\n";
    echo "   ID: {$product->id}\n";
    echo "   SKU: {$product->sku}\n";
    echo "   Name: {$product->name}\n";
    echo "   Tax Rate: {$product->tax_rate}%\n";
    echo "   Price: {$product->price}\n\n";

    // 2. Check what ProductTransformer returns
    echo "📦 ProductTransformer Output:\n";
    echo str_repeat('-', 100) . "\n";

    try {
        $transformer = app(ProductTransformer::class);
        $prestashopData = $transformer->transformForPrestaShop($product, $client);

        // Extract product data
        $psProduct = $prestashopData['product'] ?? [];

        echo "   Reference: " . ($psProduct['reference'] ?? 'MISSING') . "\n";
        echo "   Name (first lang): ";
        if (isset($psProduct['name']['language'][0]['value'])) {
            echo substr($psProduct['name']['language'][0]['value'], 0, 50) . "\n";
        } else {
            echo "MISSING\n";
        }
        echo "   Price: " . ($psProduct['price'] ?? 'MISSING') . "\n";
        echo "   id_tax_rules_group: " . ($psProduct['id_tax_rules_group'] ?? 'MISSING ❌') . "\n";
        echo "   id_category_default: " . ($psProduct['id_category_default'] ?? 'MISSING ❌') . "\n";
        echo "   id_shop_default: " . ($psProduct['id_shop_default'] ?? 'MISSING ❌') . "\n";
        echo "   minimal_quantity: " . ($psProduct['minimal_quantity'] ?? 'MISSING ❌') . "\n";
        echo "   redirect_type: " . ($psProduct['redirect_type'] ?? 'MISSING ❌') . "\n";
        echo "   state: " . ($psProduct['state'] ?? 'MISSING ❌') . "\n";
        echo "   additional_delivery_times: " . ($psProduct['additional_delivery_times'] ?? 'MISSING ❌') . "\n";

        echo "\n   Categories Association:\n";
        if (isset($psProduct['associations']['categories'])) {
            foreach ($psProduct['associations']['categories'] as $cat) {
                echo "      - Category ID: " . ($cat['id'] ?? 'N/A') . "\n";
            }
        } else {
            echo "      ❌ NO CATEGORIES!\n";
        }

    } catch (\Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    }

    echo "\n";

    // 3. Find in PrestaShop via API
    echo "🔍 PrestaShop API (what's actually stored):\n";
    echo str_repeat('-', 100) . "\n";

    try {
        // Search by reference
        $response = $client->getProducts(['filter[reference]' => $sku, 'display' => 'full']);

        if (isset($response['products']) && !empty($response['products'])) {
            $psProductData = is_array($response['products'][0]) ? $response['products'][0] : (array)$response['products'][0];
            $productId = $psProductData['id'] ?? null;

            echo "   ✅ Product found in PrestaShop:\n";
            echo "      PrestaShop ID: {$productId}\n";
            echo "      Reference: " . ($psProductData['reference'] ?? 'N/A') . "\n";

            $taxRulesGroup = $psProductData['id_tax_rules_group'] ?? 'NULL';
            echo "      id_tax_rules_group: {$taxRulesGroup}";

            if ($taxRulesGroup == 6) {
                echo " ✅ (Correct - 23% VAT)\n";
            } elseif ($taxRulesGroup == 1) {
                echo " ❌ (WRONG - Should be 6!)\n";
            } else {
                echo " ⚠️ (Unexpected value!)\n";
            }

            echo "      id_category_default: " . ($psProductData['id_category_default'] ?? 'NULL') . "\n";
            echo "      Price: " . ($psProductData['price'] ?? 'NULL') . "\n";
            echo "      Active: " . ($psProductData['active'] ?? 'NULL') . "\n\n";

            // 4. Check specific_prices via API
            echo "   💰 Specific Prices:\n";

            try {
                $spResponse = $client->getSpecificPrices($productId);

                if (isset($spResponse['specific_prices']) && !empty($spResponse['specific_prices'])) {
                    echo "      ✅ Found " . count($spResponse['specific_prices']) . " specific price(s):\n";
                    foreach ($spResponse['specific_prices'] as $spData) {
                        $sp = is_array($spData) ? $spData : (array)$spData;
                        $spId = $sp['id'] ?? 'N/A';
                        $idGroup = $sp['id_group'] ?? '0';
                        $priceOverride = $sp['price'] ?? '-1';
                        $reduction = $sp['reduction'] ?? '0';
                        $reductionType = $sp['reduction_type'] ?? 'N/A';

                        echo "         SP ID: {$spId}, Group: {$idGroup}, Price: {$priceOverride}, Reduction: {$reduction} ({$reductionType})\n";
                    }
                } else {
                    echo "      ❌ NO SPECIFIC PRICES FOUND!\n";
                    echo "      This means PrestaShopPriceExporter was NOT called or FAILED!\n";
                }
            } catch (\Exception $e) {
                if (strpos($e->getMessage(), '404') !== false) {
                    echo "      ❌ NO SPECIFIC PRICES FOUND (404)!\n";
                    echo "      This means PrestaShopPriceExporter was NOT called or FAILED!\n";
                } else {
                    echo "      ❌ ERROR fetching specific prices: " . $e->getMessage() . "\n";
                }
            }

        } else {
            echo "   ❌ Product NOT found in PrestaShop!\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ ERROR searching PrestaShop: " . $e->getMessage() . "\n";
    }

    echo "\n\n";
}

echo "=== DIAGNOSTIC COMPLETED ===\n\n";

echo "🔍 ANALYSIS:\n";
echo "1. If id_tax_rules_group = 1 → ProductTransformer is NOT sending correct value OR not sending at all\n";
echo "2. If NO specific_prices → PrestaShopPriceExporter is NOT being called\n";
echo "3. Check ProductTransformer::mapTaxRate() method\n";
echo "4. Check if PrestaShopPriceExporter::exportPricesForProduct() is called after sync\n";
