<?php

/**
 * Verify database state after all 3 fixes deployed + test run
 * Expected: category_mappings contains PITGANG (PPM 41 → PS 12) + auto-injected roots
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "   DATABASE VERIFICATION: After Fix #3 Deployment\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. Check product_shop_data for product 11034, shop 1
$psd = DB::table('product_shop_data')
    ->where('product_id', 11034)
    ->where('shop_id', 1)
    ->first();

if (!$psd) {
    echo "❌ ERROR: product_shop_data NOT FOUND for product 11034, shop 1\n\n";
    exit(1);
}

echo "1️⃣ Product Shop Data (product_id=11034, shop_id=1):\n";
echo "   Updated at: " . $psd->updated_at . "\n\n";

// 2. Parse category_mappings JSON
$categoryMappings = json_decode($psd->category_mappings, true);

if (!$categoryMappings) {
    echo "❌ ERROR: category_mappings is NULL or invalid JSON\n\n";
    exit(1);
}

echo "2️⃣ Category Mappings (Canonical Format):\n";
echo json_encode($categoryMappings, JSON_PRETTY_PRINT) . "\n\n";

// 3. Check for PITGANG (PPM 41 → PS 12)
$uiSelected = $categoryMappings['ui']['selected'] ?? [];
$mappings = $categoryMappings['mappings'] ?? [];

echo "3️⃣ Expected Categories:\n";

// Check for PPM ID 41 (PITGANG)
if (in_array(41, $uiSelected)) {
    echo "   ✅ PITGANG (PPM 41) FOUND in ui.selected\n";

    $psId = $mappings['41'] ?? null;
    if ($psId === 12) {
        echo "   ✅ PITGANG mapping correct: PPM 41 → PS 12\n";
    } else {
        echo "   ❌ PITGANG mapping WRONG: PPM 41 → PS {$psId} (expected 12)\n";
    }
} else {
    echo "   ❌ PITGANG (PPM 41) NOT FOUND in ui.selected\n";
    echo "   Current ui.selected: " . json_encode($uiSelected) . "\n";
}

// Check for auto-injected roots (PPM 1 → PS 1, PPM 36 → PS 2)
echo "\n4️⃣ Auto-injected Roots:\n";

if (in_array(1, $uiSelected)) {
    echo "   ✅ Root 'Baza' (PPM 1) FOUND in ui.selected\n";
    $psId = $mappings['1'] ?? null;
    echo "   Mapping: PPM 1 → PS {$psId} " . ($psId === 1 ? "✅" : "❌ (expected 1)") . "\n";
} else {
    echo "   ❌ Root 'Baza' (PPM 1) NOT FOUND in ui.selected\n";
}

if (in_array(36, $uiSelected)) {
    echo "   ✅ Root 'Wszystko' (PPM 36) FOUND in ui.selected\n";
    $psId = $mappings['36'] ?? null;
    echo "   Mapping: PPM 36 → PS {$psId} " . ($psId === 2 ? "✅" : "❌ (expected 2)") . "\n";
} else {
    echo "   ❌ Root 'Wszystko' (PPM 36) NOT FOUND in ui.selected\n";
}

// 5. Check metadata
$metadata = $categoryMappings['metadata'] ?? [];
echo "\n5️⃣ Metadata:\n";
echo "   Last updated: " . ($metadata['last_updated'] ?? 'N/A') . "\n";
echo "   Source: " . ($metadata['source'] ?? 'N/A') . "\n";

// 6. Check if old product_categories table was NOT used
echo "\n6️⃣ Verify OLD architecture NOT used:\n";

$oldArchitectureRecords = DB::table('product_categories')
    ->where('product_id', 11034)
    ->where('shop_id', 1)
    ->get();

if ($oldArchitectureRecords->isEmpty()) {
    echo "   ✅ NO records in product_categories with shop_id=1 (CORRECT - old architecture disabled)\n";
} else {
    echo "   ⚠️  FOUND " . $oldArchitectureRecords->count() . " records in product_categories with shop_id=1\n";
    echo "   This should NOT happen with ETAP_07b architecture!\n";

    foreach ($oldArchitectureRecords as $record) {
        echo "   - category_id={$record->category_id}, is_primary={$record->is_primary}, updated_at={$record->updated_at}\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "   VERIFICATION COMPLETED\n";
echo "═══════════════════════════════════════════════════════════════\n\n";
