<?php

/**
 * Tax Rate Differences Analysis Script
 *
 * ETAP_07 FAZA 5.3 - Tax Rules UI Enhancement - Data Analysis
 *
 * Purpose:
 * - Analyze existing product_shop_data records
 * - Compare PPM tax rates vs PrestaShop tax rates
 * - Detect mismatches and suggest tax_rate_override values
 * - Generate recommendations for data migration
 *
 * Strategy:
 * 1. Fetch all product_shop_data records with prestashop_product_id
 * 2. For each record, fetch PrestaShop product data (id_tax_rules_group)
 * 3. Reverse map: PrestaShop group ID → PPM tax rate %
 * 4. Compare with products.tax_rate (global default)
 * 5. Identify discrepancies and suggest overrides
 *
 * Reverse Mapping Logic:
 * - Use shop's tax_rules_group_id_XX mapping
 * - If prestashop_group_id === shop->tax_rules_group_id_23 → 23.00%
 * - If prestashop_group_id === shop->tax_rules_group_id_8 → 8.00%
 * - etc.
 *
 * Output:
 * - Total records analyzed
 * - Perfect matches count
 * - Mismatches count (with details: SKU, PPM rate, PrestaShop rate)
 * - Missing in PrestaShop (sync_status != 'synced')
 * - Recommendation: Auto-populate tax_rate_override?
 *
 * @author laravel-expert
 * @date 2025-11-14
 * @version 1.0
 */

require __DIR__ . '/../vendor/autoload.php';

use App\Models\Product;
use App\Models\ProductShopData;
use App\Models\PrestaShopShop;
use App\Services\PrestaShop\PrestaShopClientFactory;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// ==========================================
// CONFIGURATION
// ==========================================

$DRY_RUN = true;  // Set to false to actually update tax_rate_override
$VERBOSE = true;  // Detailed output per product

// ==========================================
// ANALYSIS LOGIC
// ==========================================

echo "=== TAX RATE DIFFERENCES ANALYSIS ===\n\n";
echo "Started: " . now()->format('Y-m-d H:i:s') . "\n";
echo "Mode: " . ($DRY_RUN ? "DRY RUN (no changes)" : "LIVE (will update database)") . "\n\n";

// Stats counters
$totalRecords = 0;
$perfectMatches = 0;
$mismatches = 0;
$missingInPrestaShop = 0;
$apiErrors = 0;
$reverseMappingFailures = 0;

// Discrepancies details
$discrepancies = [];

// ==========================================
// HELPER FUNCTION: Reverse Map Tax Rules Group ID → Tax Rate %
// ==========================================

/**
 * Reverse map PrestaShop tax_rules_group ID to PPM tax rate %
 *
 * @param int $prestashopGroupId PrestaShop tax_rules_group ID
 * @param PrestaShopShop $shop Shop instance with tax_rules_group_id_XX mappings
 * @return float|null Tax rate % or null if no mapping found
 */
function reverseMaطTaxRulesGroup(int $prestashopGroupId, PrestaShopShop $shop): ?float
{
    if ($prestashopGroupId === $shop->tax_rules_group_id_23) {
        return 23.00;
    }
    if ($prestashopGroupId === $shop->tax_rules_group_id_8) {
        return 8.00;
    }
    if ($prestashopGroupId === $shop->tax_rules_group_id_5) {
        return 5.00;
    }
    if ($prestashopGroupId === $shop->tax_rules_group_id_0) {
        return 0.00;
    }

    return null;  // No mapping found
}

// ==========================================
// FETCH DATA
// ==========================================

echo "Fetching product_shop_data records...\n";

$productShopDataRecords = ProductShopData::with(['product', 'shop'])
    ->whereNotNull('prestashop_product_id')  // Only synced products
    ->where('sync_status', ProductShopData::STATUS_SYNCED)  // Only successfully synced
    ->get();

$totalRecords = $productShopDataRecords->count();

echo "Total records to analyze: {$totalRecords}\n\n";

if ($totalRecords === 0) {
    echo "⚠️ No synced products found. Exiting.\n";
    exit(0);
}

// ==========================================
// ANALYZE EACH RECORD
// ==========================================

echo "Analyzing tax rate differences...\n";
echo str_repeat('-', 80) . "\n";

foreach ($productShopDataRecords as $index => $shopData) {
    $product = $shopData->product;
    $shop = $shopData->shop;

    if (!$product || !$shop) {
        echo "⚠️ Missing product or shop for record ID {$shopData->id}. Skipping.\n";
        continue;
    }

    $ppmTaxRate = $product->tax_rate ?? 23.00;  // Global default
    $prestashopProductId = $shopData->prestashop_product_id;
    $sku = $product->sku;

    if ($VERBOSE) {
        echo "\n[" . ($index + 1) . "/{$totalRecords}] SKU: {$sku} | Shop: {$shop->name}\n";
        echo "  PPM Default Tax Rate: {$ppmTaxRate}%\n";
    }

    // ==========================================
    // FETCH PRESTASHOP PRODUCT
    // ==========================================

    try {
        $client = PrestaShopClientFactory::create($shop);
        $psProductData = $client->getProduct($prestashopProductId);

        if (!isset($psProductData['product']['id_tax_rules_group'])) {
            echo "  ⚠️ PrestaShop product missing id_tax_rules_group. Skipping.\n";
            $apiErrors++;
            continue;
        }

        $psTaxGroupId = (int) $psProductData['product']['id_tax_rules_group'];

        if ($VERBOSE) {
            echo "  PrestaShop tax_rules_group ID: {$psTaxGroupId}\n";
        }

    } catch (\Exception $e) {
        echo "  ❌ API Error fetching PrestaShop product {$prestashopProductId}: {$e->getMessage()}\n";
        $apiErrors++;
        continue;
    }

    // ==========================================
    // REVERSE MAP: PrestaShop Group ID → Tax Rate %
    // ==========================================

    $psTaxRate = reverseMaطTaxRulesGroup($psTaxGroupId, $shop);

    if ($psTaxRate === null) {
        echo "  ⚠️ Reverse mapping failed for group ID {$psTaxGroupId}. Shop may have incomplete tax_rules_group_id_XX config.\n";
        $reverseMappingFailures++;
        continue;
    }

    if ($VERBOSE) {
        echo "  PrestaShop Effective Tax Rate: {$psTaxRate}%\n";
    }

    // ==========================================
    // COMPARE: PPM vs PrestaShop
    // ==========================================

    if ((float) $ppmTaxRate === (float) $psTaxRate) {
        // Perfect match
        $perfectMatches++;
        if ($VERBOSE) {
            echo "  ✅ MATCH: PPM {$ppmTaxRate}% = PrestaShop {$psTaxRate}%\n";
        }
    } else {
        // Mismatch detected
        $mismatches++;
        echo "  ⚠️ MISMATCH: PPM {$ppmTaxRate}% ≠ PrestaShop {$psTaxRate}%\n";
        echo "    → Suggested Action: SET tax_rate_override = {$psTaxRate}\n";

        $discrepancies[] = [
            'product_id' => $product->id,
            'sku' => $sku,
            'shop_id' => $shop->id,
            'shop_name' => $shop->name,
            'ppm_tax_rate' => $ppmTaxRate,
            'prestashop_tax_rate' => $psTaxRate,
            'prestashop_group_id' => $psTaxGroupId,
            'product_shop_data_id' => $shopData->id,
        ];

        // ==========================================
        // AUTO-UPDATE (if not DRY_RUN)
        // ==========================================

        if (!$DRY_RUN) {
            DB::table('product_shop_data')
                ->where('id', $shopData->id)
                ->update([
                    'tax_rate_override' => $psTaxRate,
                    'updated_at' => now(),
                ]);

            echo "    ✅ UPDATED: tax_rate_override set to {$psTaxRate}%\n";
        }
    }
}

echo "\n" . str_repeat('-', 80) . "\n";

// ==========================================
// SUMMARY REPORT
// ==========================================

echo "\n=== ANALYSIS SUMMARY ===\n\n";
echo "Total records analyzed: {$totalRecords}\n";
echo "✅ Perfect matches: {$perfectMatches} (" . round(($perfectMatches / $totalRecords) * 100, 2) . "%)\n";
echo "⚠️ Mismatches detected: {$mismatches} (" . round(($mismatches / $totalRecords) * 100, 2) . "%)\n";
echo "❌ API errors: {$apiErrors}\n";
echo "⚠️ Reverse mapping failures: {$reverseMappingFailures}\n";

echo "\n";

// ==========================================
// DETAILED DISCREPANCIES
// ==========================================

if (count($discrepancies) > 0) {
    echo "=== DETAILED DISCREPANCIES ===\n\n";

    foreach ($discrepancies as $d) {
        echo "---\n";
        echo "SKU: {$d['sku']}\n";
        echo "  Shop: {$d['shop_name']} (ID: {$d['shop_id']})\n";
        echo "  PPM Default: {$d['ppm_tax_rate']}%\n";
        echo "  PrestaShop: {$d['prestashop_tax_rate']}% (Group ID: {$d['prestashop_group_id']})\n";
        echo "  ⚠️ MISMATCH → Suggested: tax_rate_override = {$d['prestashop_tax_rate']}\n";
        echo "  product_shop_data ID: {$d['product_shop_data_id']}\n";
    }

    echo "\n";
}

// ==========================================
// RECOMMENDATIONS
// ==========================================

echo "=== RECOMMENDATIONS ===\n\n";

if ($mismatches === 0) {
    echo "✅ No mismatches detected. All tax rates are consistent between PPM and PrestaShop.\n";
    echo "   No action required.\n";
} else {
    echo "⚠️ {$mismatches} mismatches detected.\n\n";
    echo "Options:\n";
    echo "1. AUTO-POPULATE tax_rate_override for all mismatches:\n";
    echo "   → Run this script with \$DRY_RUN = false to auto-update database\n";
    echo "   → This will preserve existing PrestaShop tax rates per shop\n\n";
    echo "2. MANUAL REVIEW:\n";
    echo "   → Review each discrepancy above\n";
    echo "   → Decide: Should PPM override PrestaShop, or vice versa?\n";
    echo "   → Update tax_rate_override manually via ProductForm UI\n\n";
    echo "3. SYNC FROM PPM (override PrestaShop):\n";
    echo "   → Leave tax_rate_override = NULL (use PPM default)\n";
    echo "   → Trigger re-sync to update PrestaShop with PPM tax rates\n";
}

echo "\n";

// ==========================================
// EXPORT TO FILE (Optional)
// ==========================================

if (count($discrepancies) > 0) {
    $exportPath = __DIR__ . '/tax_rate_differences_report_' . now()->format('Y-m-d_His') . '.json';
    file_put_contents($exportPath, json_encode([
        'generated_at' => now()->toIso8601String(),
        'total_records' => $totalRecords,
        'perfect_matches' => $perfectMatches,
        'mismatches' => $mismatches,
        'api_errors' => $apiErrors,
        'reverse_mapping_failures' => $reverseMappingFailures,
        'discrepancies' => $discrepancies,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    echo "📁 Detailed report exported to: {$exportPath}\n\n";
}

echo "Completed: " . now()->format('Y-m-d H:i:s') . "\n";
echo "\n";
