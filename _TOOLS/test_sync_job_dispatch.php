<?php

/**
 * Test Sync Job Dispatch
 *
 * Manually dispatches SyncProductToPrestaShop job to queue
 * for verification of job execution workflow
 *
 * FAZA 3B.3 - TEST 1: Job Execution
 *
 * Usage:
 *   php _TOOLS/test_sync_job_dispatch.php <product_id> [shop_id]
 *
 * Example:
 *   php _TOOLS/test_sync_job_dispatch.php 123
 *   php _TOOLS/test_sync_job_dispatch.php 123 2
 *
 * Then run queue worker:
 *   php artisan queue:work --verbose
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Jobs\PrestaShop\SyncProductToPrestaShop;
use Illuminate\Support\Facades\Log;

echo "=== TEST SYNC JOB DISPATCH ===\n\n";

// Parse arguments
$productId = $argv[1] ?? null;
$shopId = $argv[2] ?? 1; // Default to shop ID 1

if (!$productId) {
    echo "❌ ERROR: Product ID required\n";
    echo "Usage: php _TOOLS/test_sync_job_dispatch.php <product_id> [shop_id]\n";
    echo "\nExample:\n";
    echo "  php _TOOLS/test_sync_job_dispatch.php 123\n";
    echo "  php _TOOLS/test_sync_job_dispatch.php 123 2\n";
    exit(1);
}

try {
    // Find product
    $product = Product::with(['categories', 'prices', 'stock'])->find($productId);

    if (!$product) {
        echo "❌ ERROR: Product not found (ID: {$productId})\n";
        echo "\nCheck available products:\n";
        echo "  SELECT id, sku, name FROM products ORDER BY id DESC LIMIT 10;\n";
        exit(1);
    }

    echo "✅ Product found:\n";
    echo "  ID: {$product->id}\n";
    echo "  SKU: {$product->sku}\n";
    echo "  Name: {$product->name}\n";
    echo "  Active: " . ($product->is_active ? 'Yes' : 'No') . "\n";
    echo "  Categories: " . $product->categories->count() . "\n";
    echo "  Prices: " . $product->prices->count() . "\n";
    echo "  Stock records: " . $product->stock->count() . "\n";
    echo "\n";

    // Find shop
    $shop = PrestaShopShop::find($shopId);

    if (!$shop) {
        echo "❌ ERROR: Shop not found (ID: {$shopId})\n";
        echo "\nCheck available shops:\n";
        echo "  SELECT id, name, domain, is_active FROM prestashop_shops;\n";
        exit(1);
    }

    echo "✅ Shop found:\n";
    echo "  ID: {$shop->id}\n";
    echo "  Name: {$shop->name}\n";
    echo "  Domain: {$shop->domain}\n";
    echo "  Active: " . ($shop->is_active ? 'Yes' : 'No') . "\n";
    echo "  Version: {$shop->version}\n";
    echo "\n";

    // Check if shop is active
    if (!$shop->is_active) {
        echo "⚠️ WARNING: Shop is not active - job will fail!\n";
        echo "Continue anyway? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) !== 'y') {
            echo "Aborted.\n";
            exit(0);
        }
    }

    // Dispatch job
    echo "Dispatching job...\n";

    SyncProductToPrestaShop::dispatch($product, $shop);

    echo "✅ Job dispatched successfully!\n\n";

    echo "=== NEXT STEPS ===\n";
    echo "1. Run queue worker in another terminal:\n";
    echo "   php artisan queue:work --verbose\n\n";

    echo "2. Monitor laravel.log:\n";
    echo "   Get-Content storage/logs/laravel.log -Wait -Tail 50\n\n";

    echo "3. Check job execution:\n";
    echo "   Look for: 'Product sync job started' and 'Product sync job completed'\n\n";

    echo "4. Verify sync status:\n";
    echo "   SELECT * FROM product_shop_data WHERE product_id = {$product->id} AND shop_id = {$shop->id};\n\n";

    echo "5. Check sync logs:\n";
    echo "   SELECT * FROM sync_logs WHERE product_id = {$product->id} AND shop_id = {$shop->id} ORDER BY created_at DESC LIMIT 5;\n\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: Job dispatch failed\n";
    echo "Message: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    echo "\nStack trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
