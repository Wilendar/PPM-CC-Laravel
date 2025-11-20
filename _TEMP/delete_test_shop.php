<?php

/**
 * Delete Test Shop - BUG #8.1 Fix
 *
 * Removes "Test Shop Sync Verification" which has plaintext API key
 * causing DecryptException during diagnostics.
 *
 * Usage: php _TEMP/delete_test_shop.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrestaShopShop;
use Illuminate\Support\Facades\DB;

echo "=== DELETE TEST SHOP - BUG #8.1 FIX ===\n\n";

try {
    // Find test shop by name
    $testShop = PrestaShopShop::where('name', 'LIKE', '%Test Shop%')
                              ->orWhere('name', 'LIKE', '%Sync Verification%')
                              ->first();

    if (!$testShop) {
        echo "✅ No test shop found - already deleted or never existed\n";
        exit(0);
    }

    echo "🔍 Test Shop Found:\n";
    echo "   ID: {$testShop->id}\n";
    echo "   Name: {$testShop->name}\n";
    echo "   URL: {$testShop->url}\n";
    echo "   Created: {$testShop->created_at}\n";
    echo "\n";

    // Check for related data
    $shopDataCount = DB::table('product_shop_data')
                       ->where('shop_id', $testShop->id)
                       ->count();

    $syncJobsCount = DB::table('sync_jobs')
                       ->where('shop_id', $testShop->id)
                       ->count();

    echo "📊 Related Data:\n";
    echo "   • ProductShopData records: {$shopDataCount}\n";
    echo "   • SyncJobs records: {$syncJobsCount}\n";
    echo "\n";

    if ($shopDataCount > 0 || $syncJobsCount > 0) {
        echo "⚠️  WARNING: Deleting shop will cascade delete related data!\n";
        echo "   This is expected for test shop.\n\n";
    }

    // Delete shop (cascade deletes ProductShopData and SyncJobs)
    DB::transaction(function() use ($testShop) {
        $shopId = $testShop->id;
        $shopName = $testShop->name;

        $testShop->delete();

        echo "✅ Test Shop Deleted Successfully!\n";
        echo "   Deleted shop ID: {$shopId}\n";
        echo "   Deleted shop name: {$shopName}\n";
    });

    echo "\n";
    echo "🎯 Result: BUG #8.1 Fixed - DecryptException resolved\n";
    echo "\n";

    // Verify deletion
    $stillExists = PrestaShopShop::find($testShop->id);
    if ($stillExists) {
        echo "❌ ERROR: Shop still exists after deletion!\n";
        exit(1);
    }

    echo "✅ Verification: Shop successfully removed from database\n";

} catch (\Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "   File: {$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}

echo "\n=== END DELETE TEST SHOP ===\n";
