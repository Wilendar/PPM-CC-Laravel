<?php
// TEST E2E: Category Sync PPM → PrestaShop with DB verification

require 'vendor/autoload.php';

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST E2E: Category Sync PPM → PrestaShop (DB Verification)  ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// ═══════════════════════════════════════════════════════════════════
// KONFIGURACJA TESTU
// ═══════════════════════════════════════════════════════════════════

$testProductId = 11033; // Product ID w PPM
$testShopId = 1;       // Shop ID (pitbike.pl)

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
    echo "   Structure: " . json_encode($shopData->category_mappings, JSON_PRETTY_PRINT) . "\n\n";

    if (isset($shopData->category_mappings['ui']['selected'])) {
        echo "   UI Selected (PPM IDs): " . implode(', ', $shopData->category_mappings['ui']['selected']) . "\n";
    }

    if (isset($shopData->category_mappings['mappings'])) {
        echo "   Mappings (PPM → PrestaShop):\n";
        foreach ($shopData->category_mappings['mappings'] as $ppmId => $psId) {
            echo "      PPM $ppmId → PrestaShop $psId\n";
        }
    }
} else {
    echo "   ⚠️  NULL or empty\n";
}

echo "\n";

// ═══════════════════════════════════════════════════════════════════
// STEP 2: GET CURRENT STATE (PrestaShop DB)
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 2: Current State in PrestaShop DB ═══\n";

try {
    // Connect to PrestaShop database
    // Try decrypt first, fallback to plain password
    try {
        $dbPassword = decrypt($shop->db_password);
    } catch (\Exception $e) {
        // Password not encrypted or already decrypted
        $dbPassword = $shop->db_password;
    }

    $psDbConfig = [
        'driver' => 'mysql',
        'host' => $shop->db_host,
        'port' => $shop->db_port ?? 3306,
        'database' => $shop->db_name,
        'username' => $shop->db_user,
        'password' => $dbPassword,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ];

    config(['database.connections.prestashop_test' => $psDbConfig]);
    DB::purge('prestashop_test');

    $prestashopProductId = $shopData->prestashop_product_id;

    // Query ps_category_product table
    $psCategories = DB::connection('prestashop_test')
        ->table('ps_category_product')
        ->where('id_product', $prestashopProductId)
        ->pluck('id_category')
        ->toArray();

    echo "✅ PrestaShop categories (ps_category_product):\n";
    echo "   Product ID: $prestashopProductId\n";
    echo "   Category IDs: " . implode(', ', $psCategories) . "\n\n";

    // Store for later comparison
    $currentPsCategories = $psCategories;

} catch (\Exception $e) {
    echo "❌ ERROR connecting to PrestaShop DB: " . $e->getMessage() . "\n";
    exit(1);
}

// ═══════════════════════════════════════════════════════════════════
// STEP 3: COMPARISON - PPM vs PrestaShop
// ═══════════════════════════════════════════════════════════════════

echo "═══ STEP 3: Comparison (PPM vs PrestaShop) ═══\n";

if (isset($shopData->category_mappings['mappings'])) {
    $ppmPsIds = array_values($shopData->category_mappings['mappings']);
    sort($ppmPsIds);
    sort($currentPsCategories);

    echo "PPM expects (from mappings):    " . implode(', ', $ppmPsIds) . "\n";
    echo "PrestaShop has (from DB):       " . implode(', ', $currentPsCategories) . "\n\n";

    if ($ppmPsIds === $currentPsCategories) {
        echo "✅ MATCH - Categories are synchronized!\n\n";
    } else {
        echo "❌ MISMATCH - Categories are OUT OF SYNC!\n\n";

        $missing = array_diff($ppmPsIds, $currentPsCategories);
        $extra = array_diff($currentPsCategories, $ppmPsIds);

        if (!empty($missing)) {
            echo "   Missing in PrestaShop: " . implode(', ', $missing) . "\n";
        }
        if (!empty($extra)) {
            echo "   Extra in PrestaShop: " . implode(', ', $extra) . "\n";
        }

        echo "\n";
    }
} else {
    echo "⚠️  Cannot compare - no mappings in PPM\n\n";
}

// ═══════════════════════════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════════════════════════

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  TEST COMPLETE - Review results above                         ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "📊 Next Steps:\n";
echo "   1. If MISMATCH → Trigger sync: 'Aktualizuj aktualny sklep' in PPM\n";
echo "   2. Wait for sync job to complete\n";
echo "   3. Re-run this script to verify sync worked\n\n";
