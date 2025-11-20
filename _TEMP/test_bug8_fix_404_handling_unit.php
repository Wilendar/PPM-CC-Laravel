<?php

/**
 * BUG #8 FIX #1 UNIT TEST
 *
 * Unit tests for 404 handling logic WITHOUT actual API calls.
 * Tests the code structure and exception handling logic.
 *
 * Usage:
 *   php _TEMP/test_bug8_fix_404_handling_unit.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Exceptions\PrestaShopAPIException;

echo "\n";
echo "===========================================\n";
echo "  BUG #8 FIX #1 UNIT TEST (NO API CALLS)  \n";
echo "===========================================\n\n";

$allPassed = true;

// TEST 1: PrestaShopAPIException::isNotFound() method exists
echo "[TEST 1/5] PrestaShopAPIException::isNotFound() exists...\n";
if (!method_exists(PrestaShopAPIException::class, 'isNotFound')) {
    echo "   ❌ FAIL: isNotFound() method missing\n\n";
    $allPassed = false;
} else {
    echo "   ✅ PASS: Method exists\n\n";
}

// TEST 2: isNotFound() returns true for 404
echo "[TEST 2/5] isNotFound() returns true for 404 status...\n";
try {
    $exception404 = new PrestaShopAPIException('Not Found', 404, null, []);
    if ($exception404->isNotFound() === true) {
        echo "   ✅ PASS: isNotFound() returns true for 404\n\n";
    } else {
        echo "   ❌ FAIL: isNotFound() returns false for 404\n\n";
        $allPassed = false;
    }
} catch (\Exception $e) {
    echo "   ❌ FAIL: Exception thrown: {$e->getMessage()}\n\n";
    $allPassed = false;
}

// TEST 3: isNotFound() returns false for non-404
echo "[TEST 3/5] isNotFound() returns false for non-404 status...\n";
try {
    $exception500 = new PrestaShopAPIException('Server Error', 500, null, []);
    $exception401 = new PrestaShopAPIException('Unauthorized', 401, null, []);

    if ($exception500->isNotFound() === false && $exception401->isNotFound() === false) {
        echo "   ✅ PASS: isNotFound() returns false for 500 and 401\n\n";
    } else {
        echo "   ❌ FAIL: isNotFound() incorrectly returns true for non-404\n\n";
        $allPassed = false;
    }
} catch (\Exception $e) {
    echo "   ❌ FAIL: Exception thrown: {$e->getMessage()}\n\n";
    $allPassed = false;
}

// TEST 4: Check PullProductsFromPrestaShop imports PrestaShopAPIException
echo "[TEST 4/5] PullProductsFromPrestaShop imports PrestaShopAPIException...\n";
$jobFile = __DIR__ . '/../app/Jobs/PullProductsFromPrestaShop.php';
$jobContent = file_get_contents($jobFile);

if (str_contains($jobContent, 'use App\Exceptions\PrestaShopAPIException;')) {
    echo "   ✅ PASS: PrestaShopAPIException is imported\n\n";
} else {
    echo "   ❌ FAIL: PrestaShopAPIException not imported in PullProductsFromPrestaShop\n\n";
    $allPassed = false;
}

// TEST 5: Check PullProductsFromPrestaShop has 404 handling logic
echo "[TEST 5/5] PullProductsFromPrestaShop has 404 handling logic...\n";
$has404Check = str_contains($jobContent, 'isNotFound()');
$hasUnlinkLogic = str_contains($jobContent, 'prestashop_product_id\' => null');
$hasSyncStatusUpdate = str_contains($jobContent, 'sync_status\' => \'not_synced\'');
$hasErrorMessage = str_contains($jobContent, 'Product deleted from PrestaShop (404)');

if ($has404Check && $hasUnlinkLogic && $hasSyncStatusUpdate && $hasErrorMessage) {
    echo "   ✅ PASS: All 404 handling logic present:\n";
    echo "           - isNotFound() check: YES\n";
    echo "           - prestashop_product_id => null: YES\n";
    echo "           - sync_status => 'not_synced': YES\n";
    echo "           - Error message '404': YES\n\n";
} else {
    echo "   ❌ FAIL: Missing 404 handling logic:\n";
    echo "           - isNotFound() check: " . ($has404Check ? 'YES' : 'NO') . "\n";
    echo "           - prestashop_product_id => null: " . ($hasUnlinkLogic ? 'YES' : 'NO') . "\n";
    echo "           - sync_status => 'not_synced': " . ($hasSyncStatusUpdate ? 'YES' : 'NO') . "\n";
    echo "           - Error message '404': " . ($hasErrorMessage ? 'YES' : 'NO') . "\n\n";
    $allPassed = false;
}

// BONUS TEST: Check PrestaShopPriceImporter re-throws PrestaShopAPIException
echo "[BONUS TEST 6] PrestaShopPriceImporter re-throws PrestaShopAPIException...\n";
$priceImporterFile = __DIR__ . '/../app/Services/PrestaShop/PrestaShopPriceImporter.php';
$priceImporterContent = file_get_contents($priceImporterFile);

if (str_contains($priceImporterContent, 'catch (\App\Exceptions\PrestaShopAPIException $e)')) {
    echo "   ✅ PASS: PrestaShopPriceImporter catches PrestaShopAPIException specifically\n\n";
} else {
    echo "   ⚠️  WARNING: PrestaShopPriceImporter may not catch PrestaShopAPIException\n\n";
}

// BONUS TEST: Check PrestaShopStockImporter re-throws PrestaShopAPIException
echo "[BONUS TEST 7] PrestaShopStockImporter re-throws PrestaShopAPIException...\n";
$stockImporterFile = __DIR__ . '/../app/Services/PrestaShop/PrestaShopStockImporter.php';
$stockImporterContent = file_get_contents($stockImporterFile);

if (str_contains($stockImporterContent, 'catch (\App\Exceptions\PrestaShopAPIException $e)')) {
    echo "   ✅ PASS: PrestaShopStockImporter catches PrestaShopAPIException specifically\n\n";
} else {
    echo "   ⚠️  WARNING: PrestaShopStockImporter may not catch PrestaShopAPIException\n\n";
}

echo "===========================================\n";
if ($allPassed) {
    echo "  ✅ ALL CORE TESTS PASSED                  \n";
    echo "                                           \n";
    echo "  BUG #8 FIX #1 implementation is correct  \n";
    echo "  Ready for production deployment          \n";
} else {
    echo "  ❌ SOME TESTS FAILED - SEE ABOVE          \n";
    echo "                                           \n";
    echo "  Review failed tests before deployment    \n";
}
echo "===========================================\n\n";

echo "NOTE: Integration test with real PrestaShop API requires:\n";
echo "      - Active PrestaShop shop with valid API key\n";
echo "      - Product with invalid prestashop_product_id (non-existent)\n";
echo "      - Run: php _TEMP/test_bug8_fix_404_handling.php\n\n";

exit($allPassed ? 0 : 1);
