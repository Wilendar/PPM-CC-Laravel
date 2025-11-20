<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "   DATABASE VERIFICATION (PRODUCTION)\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$productId = 11034;
$shopId = 1;

// Check product_shop_data
$psd = DB::table('product_shop_data')
    ->where('product_id', $productId)
    ->where('shop_id', $shopId)
    ->first();

if (!$psd) {
    echo "❌ product_shop_data NOT FOUND (product_id={$productId}, shop_id={$shopId})\n\n";
    exit(1);
}

echo "1️⃣ RECORD CHECK:\n\n";
echo "   ✅ Record exists (ID: {$psd->id})\n";
echo "   Updated at: {$psd->updated_at}\n\n";

if (!$psd->category_mappings) {
    echo "❌ category_mappings is NULL or empty\n\n";
    exit(1);
}

echo "2️⃣ CATEGORY_MAPPINGS (JSON):\n\n";
$mappings = json_decode($psd->category_mappings, true);
echo json_encode($mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

if (!isset($mappings['ui']['selected'])) {
    echo "❌ Invalid structure - missing 'ui.selected'\n\n";
    exit(1);
}

echo "3️⃣ ANALYSIS:\n\n";
echo "   Selected PPM IDs: " . implode(', ', $mappings['ui']['selected']) . "\n";
echo "   Primary PPM ID: " . ($mappings['ui']['primary'] ?? 'NULL') . "\n\n";

// Check PITGANG (PPM 41 -> PS 12)
$pitgangSaved = in_array(41, $mappings['ui']['selected'])
    && isset($mappings['mappings']['41'])
    && $mappings['mappings']['41'] == 12;

if ($pitgangSaved) {
    echo "   ✅ SUCCESS: PITGANG (PPM 41 → PS 12) IS SAVED!\n";
} else {
    echo "   ❌ FAIL: PITGANG (PPM 41 → PS 12) NOT FOUND\n";
}

// Check auto-injected roots
$rootsSaved = in_array(1, $mappings['ui']['selected']) && in_array(36, $mappings['ui']['selected']);

if ($rootsSaved) {
    echo "   ✅ SUCCESS: Auto-injected roots (PPM 1 + 36) PRESENT\n\n";
} else {
    echo "   ❌ FAIL: Auto-injected roots NOT PRESENT\n\n";
}

// Timestamp
if (isset($mappings['metadata']['last_updated'])) {
    echo "   Last updated: " . $mappings['metadata']['last_updated'] . "\n";
}

echo "\n";
echo "4️⃣ FINAL VERDICT:\n\n";

if ($pitgangSaved && $rootsSaved) {
    echo "   🎉 ALL CHECKS PASSED - FIX IS WORKING!\n\n";
    exit(0);
} else {
    echo "   ⚠️  SOME CHECKS FAILED - INVESTIGATION NEEDED\n\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n\n";
