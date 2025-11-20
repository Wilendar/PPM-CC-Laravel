<?php

/**
 * DIAGNOSTIC: Specific Prices Missing After Sync
 *
 * ISSUE: Products synced to PrestaShop but no specific_prices created
 * PRODUCTS: PB-KAYO-E-KMB #11033, Q-KAYO-EA70 #11034
 * SHOP: B2B Test DEV (ID: 1)
 *
 * DIAGNOSIS CHECKLIST:
 * 1. Check if products have product_prices records in PPM
 * 2. Check if shop has price group mappings
 * 3. Check ProductShopData for external IDs
 * 4. Simulate price export process
 * 5. Check PrestaShop specific_prices table
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ShopMapping;
use App\Models\PriceGroup;
use App\Services\PrestaShop\PriceGroupMapper;
use App\Services\PrestaShop\PrestaShopPriceExporter;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\DB;

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DIAGNOSTIC: Specific Prices Missing ===\n\n";

// Test products
$testProducts = [
    ['sku' => 'PB-KAYO-E-KMB', 'id' => 11033],
    ['sku' => 'Q-KAYO-EA70', 'id' => 11034],
];

$shop = PrestaShopShop::find(1);

if (!$shop) {
    echo "❌ ERROR: Shop ID 1 not found\n";
    exit(1);
}

echo "✅ Shop: {$shop->name} (ID: {$shop->id})\n\n";

// Check 1: Price Group Mappings
echo "--- CHECK 1: Price Group Mappings ---\n";
$mappings = ShopMapping::where('shop_id', $shop->id)
    ->where('mapping_type', ShopMapping::TYPE_PRICE_GROUP)
    ->where('is_active', true)
    ->get();

echo "Price group mappings count: " . $mappings->count() . "\n";

if ($mappings->isEmpty()) {
    echo "⚠️  WARNING: NO PRICE GROUP MAPPINGS FOUND!\n";
    echo "   This is the ROOT CAUSE - PriceExporter will skip ALL prices\n\n";

    // Show available price groups
    $priceGroups = PriceGroup::where('is_active', true)->get();
    echo "Available PPM Price Groups:\n";
    foreach ($priceGroups as $pg) {
        echo "  - ID: {$pg->id}, Code: {$pg->code}, Name: {$pg->name}\n";
    }
    echo "\n";
} else {
    echo "Mapped price groups:\n";
    foreach ($mappings as $m) {
        $pg = PriceGroup::find((int)$m->ppm_value);
        $pgCode = $pg ? $pg->code : 'N/A';
        echo "  - PPM: {$m->ppm_value} ({$pgCode}) → PrestaShop: {$m->prestashop_id}\n";
    }
    echo "\n";
}

// Check 2: Product Prices
foreach ($testProducts as $testProduct) {
    echo "--- CHECK 2: Product {$testProduct['sku']} (ID: {$testProduct['id']}) ---\n";

    $product = Product::find($testProduct['id']);

    if (!$product) {
        echo "❌ Product not found\n\n";
        continue;
    }

    // Get product prices
    $prices = $product->prices()->with('priceGroup')->get();
    echo "Product prices count: " . $prices->count() . "\n";

    if ($prices->isEmpty()) {
        echo "⚠️  WARNING: Product has NO prices defined!\n\n";
        continue;
    }

    echo "Product prices:\n";
    foreach ($prices as $price) {
        echo "  - PriceGroup: {$price->price_group_id} ({$price->priceGroup->code}), ";
        echo "Net: {$price->price_net}, Gross: {$price->price_gross}\n";
    }
    echo "\n";

    // Check ProductShopData
    $shopData = $product->shopData()->where('shop_id', $shop->id)->first();

    if (!$shopData) {
        echo "⚠️  WARNING: No ProductShopData found for this shop\n\n";
        continue;
    }

    echo "ProductShopData:\n";
    $externalId = $shopData->prestashop_product_id ? $shopData->prestashop_product_id : 'NULL';
    $lastSynced = $shopData->last_synced_at ? $shopData->last_synced_at : 'Never';
    echo "  - External ID: {$externalId}\n";
    echo "  - Sync Status: {$shopData->sync_status}\n";
    echo "  - Last Synced: {$lastSynced}\n\n";

    if (!$shopData->prestashop_product_id) {
        echo "⚠️  WARNING: No PrestaShop external ID - product not synced yet\n\n";
        continue;
    }

    // Check 3: Simulate Price Export
    echo "--- CHECK 3: Simulate Price Export ---\n";

    try {
        $mapper = app(PriceGroupMapper::class);

        echo "Mapping simulation:\n";
        $canExport = false;

        foreach ($prices as $price) {
            $prestashopGroupId = $mapper->mapToPrestaShop($price->price_group_id, $shop);

            if ($prestashopGroupId) {
                echo "  ✅ PriceGroup {$price->price_group_id} ({$price->priceGroup->code}) → PrestaShop Group {$prestashopGroupId}\n";
                $canExport = true;
            } else {
                echo "  ❌ PriceGroup {$price->price_group_id} ({$price->priceGroup->code}) → NOT MAPPED (will be skipped)\n";
            }
        }

        if (!$canExport) {
            echo "\n⚠️  CRITICAL: ALL prices will be skipped - NO mappings found!\n";
        }

        echo "\n";

    } catch (\Exception $e) {
        echo "❌ ERROR during simulation: " . $e->getMessage() . "\n\n";
    }

    // Check 4: PrestaShop Database (if DB credentials available)
    if ($shop->db_host && $shop->db_database) {
        echo "--- CHECK 4: PrestaShop Database ---\n";

        try {
            $prestashopDb = DB::connection('prestashop_shop_' . $shop->id);

            $specificPrices = $prestashopDb->table('ps_specific_price')
                ->where('id_product', $shopData->prestashop_product_id)
                ->get();

            echo "Specific prices in PrestaShop: " . $specificPrices->count() . "\n";

            if ($specificPrices->isEmpty()) {
                echo "❌ CONFIRMED: No specific_prices in PrestaShop database\n";
            } else {
                echo "PrestaShop specific_prices:\n";
                foreach ($specificPrices as $sp) {
                    echo "  - ID: {$sp->id}, id_group: {$sp->id_group}, price: {$sp->price}\n";
                }
            }

            echo "\n";

        } catch (\Exception $e) {
            echo "⚠️  Cannot check PrestaShop DB: " . $e->getMessage() . "\n\n";
        }
    }
}

// DIAGNOSIS SUMMARY
echo "=== DIAGNOSIS SUMMARY ===\n\n";

if ($mappings->isEmpty()) {
    echo "🔴 ROOT CAUSE IDENTIFIED:\n";
    echo "   NO PRICE GROUP MAPPINGS configured for shop!\n\n";
    echo "📋 SOLUTION:\n";
    echo "   1. Go to Admin → Shops → Edit Shop '{$shop->name}'\n";
    echo "   2. Configure Price Group Mappings (PPM → PrestaShop customer groups)\n";
    echo "   3. Re-sync products to create specific_prices\n\n";
    echo "💡 EXPLANATION:\n";
    echo "   PrestaShopPriceExporter.php line 198-209:\n";
    echo "   - For each product_price, it calls priceGroupMapper->mapToPrestaShop()\n";
    echo "   - If mapping returns NULL, price is skipped (action: 'skipped')\n";
    echo "   - Without ANY mappings, ALL prices are skipped\n";
    echo "   - Result: created=0, updated=0, skipped=8 (all price groups)\n\n";
} else {
    echo "✅ Price group mappings exist\n";
    echo "   Need to investigate further - check Laravel logs for:\n";
    echo "   - '[PRICE EXPORT] Failed to create specific_price'\n";
    echo "   - PrestaShop API errors\n\n";
}

echo "Done.\n";
