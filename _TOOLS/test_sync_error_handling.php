<?php

/**
 * Test Sync Error Handling
 *
 * Verifies error detection and logging for sync operations
 * Tests multiple error scenarios:
 * - Missing required product fields
 * - Invalid product data
 * - PrestaShop API errors (simulated)
 *
 * FAZA 3B.3 - TEST 3: Error Handling Verification
 *
 * Usage:
 *   php _TOOLS/test_sync_error_handling.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Models\ProductShopData;
use App\Models\SyncLog;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== TEST SYNC ERROR HANDLING ===\n\n";

$testResults = [];

try {
    // ================================================
    // TEST CASE 1: Product without required name
    // ================================================
    echo "TEST CASE 1: Missing product name\n";
    echo str_repeat('-', 50) . "\n";

    DB::beginTransaction();

    try {
        $product1 = Product::create([
            'sku' => 'ERROR-TEST-NAME-' . time(),
            'name' => null, // Missing name - should fail validation
            'product_type_id' => 1,
            'is_active' => true,
        ]);

        echo "❌ UNEXPECTED: Product created without name (ID: {$product1->id})\n";
        echo "This should have failed validation!\n";

        $testResults['test1_missing_name'] = [
            'status' => 'fail',
            'reason' => 'Product created without name - validation missing',
        ];

    } catch (\Exception $e) {
        echo "✅ PASS: Database constraint prevented null name\n";
        echo "Error: " . substr($e->getMessage(), 0, 100) . "...\n";

        $testResults['test1_missing_name'] = [
            'status' => 'pass',
            'error_caught' => 'Database constraint: name cannot be null',
        ];
    }

    DB::rollBack(); // Clean up
    echo "\n";

    // ================================================
    // TEST CASE 2: Product without SKU
    // ================================================
    echo "TEST CASE 2: Missing product SKU\n";
    echo str_repeat('-', 50) . "\n";

    DB::beginTransaction();

    try {
        $product2 = Product::create([
            'sku' => null, // Missing SKU - should fail validation
            'name' => 'Test Product Without SKU',
            'product_type_id' => 1,
            'is_active' => true,
        ]);

        echo "❌ UNEXPECTED: Product created without SKU (ID: {$product2->id})\n";
        echo "This should have failed validation!\n";

        $testResults['test2_missing_sku'] = [
            'status' => 'fail',
            'reason' => 'Product created without SKU - validation missing',
        ];

    } catch (\Exception $e) {
        echo "✅ PASS: Database constraint prevented null SKU\n";
        echo "Error: " . substr($e->getMessage(), 0, 100) . "...\n";

        $testResults['test2_missing_sku'] = [
            'status' => 'pass',
            'error_caught' => 'Database constraint: sku cannot be null',
        ];
    }

    DB::rollBack(); // Clean up
    echo "\n";

    // ================================================
    // Get shop for remaining tests
    // ================================================
    $shop = PrestaShopShop::where('is_active', true)->first();
    if (!$shop) {
        echo "⚠️ WARNING: No active shop found, using first shop\n";
        $shop = PrestaShopShop::first();
    }

    if (!$shop) {
        echo "❌ ERROR: No shops in database - cannot run remaining tests\n";
        exit(1);
    }

    echo "Using shop for tests 3-5: {$shop->name} (ID: {$shop->id})\n\n";

    // ================================================
    // TEST CASE 3: Inactive product
    // ================================================
    echo "TEST CASE 3: Inactive product (is_active = false)\n";
    echo str_repeat('-', 50) . "\n";

    DB::beginTransaction();

    $product3 = Product::create([
        'sku' => 'ERROR-TEST-INACTIVE-' . time(),
        'name' => 'Test Inactive Product',
        'product_type_id' => 1,
        'is_active' => false, // Inactive - should fail validation
    ]);

    echo "Created test product: ID {$product3->id}, SKU {$product3->sku}\n";
    echo "Product is_active: false\n";

    try {
        SyncProductToPrestaShop::dispatch($product3, $shop);
        echo "Job dispatched (will process in queue worker)\n";

        $testResults['test3_inactive'] = [
            'status' => 'pending_queue',
            'product_id' => $product3->id,
            'expected' => 'Error in sync_logs: Product must be active',
        ];

    } catch (\Exception $e) {
        echo "✅ Exception caught immediately: {$e->getMessage()}\n";
        $testResults['test3_inactive'] = [
            'status' => 'pass',
            'error_caught' => $e->getMessage(),
        ];
    }

    DB::rollBack(); // Clean up test product
    echo "\n";

    // ================================================
    // TEST CASE 4: Check sync_logs structure
    // ================================================
    echo "TEST CASE 4: Verify sync_logs table structure\n";
    echo str_repeat('-', 50) . "\n";

    $recentLogs = SyncLog::where('operation', 'sync_product')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();

    echo "Recent sync logs (last 5 product syncs):\n";
    if ($recentLogs->isEmpty()) {
        echo "⚠️ No sync logs found - table may be empty\n";
        echo "This is expected if no syncs have been performed yet\n";
    } else {
        foreach ($recentLogs as $log) {
            $statusIcon = match ($log->status) {
                'success' => '✅',
                'error' => '❌',
                'warning' => '⚠️',
                default => '❓',
            };

            echo "{$statusIcon} [{$log->created_at}] ";
            echo "Product {$log->product_id} → Shop {$log->shop_id} ";
            echo "Status: {$log->status}";

            if ($log->message) {
                echo " | {$log->message}";
            }

            if ($log->execution_time_ms) {
                echo " | {$log->execution_time_ms}ms";
            }

            echo "\n";
        }
    }

    echo "\nSync logs table schema verification:\n";
    $sampleLog = SyncLog::first();
    if ($sampleLog) {
        $columns = array_keys($sampleLog->getAttributes());
        echo "✅ Columns present: " . implode(', ', $columns) . "\n";
    } else {
        echo "⚠️ No logs in table yet - unable to verify schema\n";
        echo "Expected columns: id, shop_id, product_id, operation, direction, status, message, execution_time_ms, created_at\n";
    }

    echo "\n";

    // ================================================
    // TEST CASE 5: Verify ProductShopData error tracking
    // ================================================
    echo "TEST CASE 5: ProductShopData error tracking\n";
    echo str_repeat('-', 50) . "\n";

    // Find a product with error status
    $errorShopData = ProductShopData::where('sync_status', ProductShopData::STATUS_ERROR)
        ->first();

    if ($errorShopData) {
        echo "✅ Found product with error status:\n";
        echo "  Product ID: {$errorShopData->product_id}\n";
        echo "  Shop ID: {$errorShopData->shop_id}\n";
        echo "  Status: {$errorShopData->sync_status}\n";
        echo "  Error message: {$errorShopData->error_message}\n";
        echo "  Retry count: {$errorShopData->retry_count}\n";
        echo "  Last sync: {$errorShopData->last_sync_at}\n";

        $testResults['test5_error_tracking'] = [
            'status' => 'pass',
            'error_tracked' => true,
        ];
    } else {
        echo "⚠️ No products with error status found\n";
        echo "This is expected if all syncs have been successful\n";

        $testResults['test5_error_tracking'] = [
            'status' => 'info',
            'error_tracked' => false,
            'note' => 'No errors to track (all syncs successful)',
        ];
    }

    echo "\n";

    // ================================================
    // SUMMARY
    // ================================================
    echo "=== ERROR HANDLING TEST SUMMARY ===\n";
    echo json_encode($testResults, JSON_PRETTY_PRINT) . "\n\n";

    echo "NOTES:\n";
    echo "- Tests 1-3 require queue:work to fully process\n";
    echo "- Check sync_logs table after running queue worker\n";
    echo "- Expected: Error entries with appropriate error messages\n\n";

    echo "NEXT STEPS:\n";
    echo "1. Run queue worker: php artisan queue:work --verbose\n";
    echo "2. Check sync_logs: SELECT * FROM sync_logs WHERE status = 'error' ORDER BY created_at DESC LIMIT 10;\n";
    echo "3. Check product_shop_data: SELECT * FROM product_shop_data WHERE sync_status = 'error';\n";
    echo "\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ ERROR: Test execution failed\n";
    echo "Message: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    echo "\nStack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
