<?php
// TEST E2E: Category Sync PPM → PrestaShop via API

require 'vendor/autoload.php';

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST E2E: Category Sync PPM → PrestaShop (API Verification)   ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════════
// KONFIGURACJA TESTU
// ═══════════════════════════════════════════════════════════════════

$testProductId = 11033; // Product ID w PPM
$testShopId = 1;       // Shop ID (B2B Test DEV)

echo "📋 Test Configuration:\n";
echo "   Product ID: $testProductId\n";
echo "   Shop ID: $testShopId\n\n";

// ═══════════════════════════════════════════════════════════════════
// STEP 1: GET CURRENT STATE (PPM)
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 1: Current State in PPM ═══\n";

$product = Product::find($testProductId);
$shop = PrestaShopShop::find($testShopId);
$shopData = ProductShopData::where('product_id', $testProductId)
    ->where('shop_id', $testShopId)
    ->first();

if (!$product || !$shop || !$shopData) {
    echo "❌ ERROR: Product, Shop, or ProductShopData NOT FOUND!\n";
    exit(1);
}

echo "✅ Product: {$product->name} (SKU: {$product->sku})\n";
echo "✅ Shop: {$shop->name}\n";
echo "✅ PrestaShop Product ID: {$shopData->prestashop_product_id}\n\n";

echo "📦 Current category_mappings in PPM (ProductShopData):\n";
if ($shopData->category_mappings) {
    if (isset($shopData->category_mappings['ui']['selected'])) {
        echo "   UI Selected (PPM IDs): " . implode(', ', $shopData->category_mappings['ui']['selected']) . "\n";
    }

    if (isset($shopData->category_mappings['mappings'])) {
        echo "   Mappings (PPM → PrestaShop):\n";
        $ppmPsIds = [];
        foreach ($shopData->category_mappings['mappings'] as $ppmId => $psId) {
            echo "      PPM $ppmId → PrestaShop $psId\n";
            $ppmPsIds[] = (int) $psId;
        }
        sort($ppmPsIds);
        echo "\n   Expected PrestaShop IDs: " . implode(', ', $ppmPsIds) . "\n";
    }
} else {
    echo "   ⚠️  NULL or empty\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// STEP 2: GET CURRENT STATE (PrestaShop API)
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 2: Current State in PrestaShop (via API) ═══\n";

try {
    $client = PrestaShopClientFactory::create($shop);
    $prestashopProductId = $shopData->prestashop_product_id;

    echo "🔌 Connecting to PrestaShop API...\n";
    echo "   URL: {$shop->api_url}\n";
    echo "   Product ID: $prestashopProductId\n\n";

    // Get product from PrestaShop
    $psProduct = $client->getProduct($prestashopProductId);

    // Unwrap nested response
    if (isset($psProduct['product'])) {
        $psProduct = $psProduct['product'];
    }

    // Extract categories
    $psCategories = $psProduct['associations']['categories'] ?? [];

    // Handle nested structure
    if (isset($psCategories['category'])) {
        $psCategories = $psCategories['category'];
    }

    // Extract IDs
    $psCategoryIds = [];
    if (is_array($psCategories)) {
        foreach ($psCategories as $cat) {
            if (isset($cat['id'])) {
                $psCategoryIds[] = (int) $cat['id'];
            }
        }
    }

    sort($psCategoryIds);

    echo "✅ PrestaShop categories (from API):\n";
    echo "   Product ID: $prestashopProductId\n";
    echo "   Category IDs: " . implode(', ', $psCategoryIds) . "\n\n";

} catch (\Exception $e) {
    echo "❌ ERROR fetching from PrestaShop API: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

// ═══════════════════════════════════════════════════════════════════
// STEP 3: COMPARISON - PPM vs PrestaShop
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 3: Comparison (PPM vs PrestaShop) ═══\n";

if (isset($ppmPsIds) && isset($psCategoryIds)) {
    echo "PPM expects (from mappings):    " . implode(', ', $ppmPsIds) . "\n";
    echo "PrestaShop has (from API):      " . implode(', ', $psCategoryIds) . "\n\n";

    if ($ppmPsIds === $psCategoryIds) {
        echo "✅ ✅ ✅ MATCH - Categories are SYNCHRONIZED! ✅ ✅ ✅\n\n";
    } else {
        echo "❌ ❌ ❌ MISMATCH - Categories are OUT OF SYNC! ❌ ❌ ❌\n\n";

        $missing = array_diff($ppmPsIds, $psCategoryIds);
        $extra = array_diff($psCategoryIds, $ppmPsIds);

        if (!empty($missing)) {
            echo "   🚨 Missing in PrestaShop (should be added): " . implode(', ', $missing) . "\n";
        }
        if (!empty($extra)) {
            echo "   ⚠️  Extra in PrestaShop (should be removed): " . implode(', ', $extra) . "\n";
        }

        echo "\n";
        echo "   This proves SYNC IS NOT WORKING!\n";
    }
} else {
    echo "⚠️  Cannot compare - missing data\n\n";
}

// ═══════════════════════════════════════════════════════════════════
// STEP 4: RECOMMENDATIONS
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 4: Next Steps ═══\n";

if (isset($ppmPsIds) && isset($psCategoryIds) && $ppmPsIds !== $psCategoryIds) {
    echo "📋 To test sync:\n";
    echo "   1. Open product in PPM: https://ppm.mpptrade.pl/admin/products/$testProductId/edit\n";
    echo "   2. Go to TAB 'Sklepy'\n";
    echo "   3. Select shop: {$shop->name}\n";
    echo "   4. Click 'Aktualizuj aktualny sklep' button\n";
    echo "   5. Wait for sync job to complete (~10 seconds)\n";
    echo "   6. Re-run this script: php test_e2e_api_category_sync.php\n";
    echo "   7. Check if MISMATCH becomes MATCH\n\n";
} else {
    echo "✅ Categories already synchronized!\n";
    echo "   To test if sync works, manually change categories in PPM and re-sync.\n\n";
}

echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST COMPLETE - Review results above                           ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n";
