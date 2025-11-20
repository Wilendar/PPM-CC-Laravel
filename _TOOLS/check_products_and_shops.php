<?php

/**
 * CHECK: Products and Shops Status
 *
 * Purpose: Comprehensive check why "Oczekuje" status appears
 *
 * Checks:
 * 1. How many products exist in products table
 * 2. How many shops exist in prestashop_shops table
 * 3. How many product-shop assignments in product_shop_data
 * 4. Recent products (last 10)
 * 5. Shops with db_workaround enabled
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PPM PRODUCTS & SHOPS DIAGNOSTICS ===\n\n";

try {
    // Step 1: Count products
    echo "Step 1: Checking products table...\n";
    $productsCount = DB::table('products')->count();
    echo "✓ Total products in database: {$productsCount}\n\n";

    if ($productsCount > 0) {
        echo "Last 5 products:\n";
        $recentProducts = DB::table('products')
            ->select(['id', 'sku', 'name', 'created_at'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        foreach ($recentProducts as $product) {
            echo "  - [ID: {$product->id}] {$product->name} (SKU: {$product->sku})\n";
            echo "    Created: {$product->created_at}\n";
        }
        echo "\n";
    }

    // Step 2: Count shops
    echo "Step 2: Checking prestashop_shops table...\n";
    $shopsCount = DB::table('prestashop_shops')->count();
    echo "✓ Total PrestaShop shops: {$shopsCount}\n\n";

    if ($shopsCount > 0) {
        echo "All shops:\n";
        $shops = DB::table('prestashop_shops')
            ->select([
                'id',
                'name',
                'url',
                'is_active',
                'connection_status'
            ])
            ->get();

        foreach ($shops as $shop) {
            echo "  - [ID: {$shop->id}] {$shop->name}\n";
            echo "    URL: {$shop->url}\n";
            echo "    Active: " . ($shop->is_active ? 'YES' : 'NO') . "\n";
            echo "    Connection: {$shop->connection_status}\n";
            echo "\n";
        }
    }

    // Step 3: Count product-shop assignments
    echo "Step 3: Checking product_shop_data table...\n";
    $assignmentsCount = DB::table('product_shop_data')->count();

    if ($assignmentsCount === 0) {
        echo "❌ NO PRODUCT-SHOP ASSIGNMENTS FOUND!\n\n";
        echo "This is why you see 'Oczekuje' status - no products are assigned to shops!\n\n";

        echo "💡 TO FIX:\n";
        echo "1. Open product in PPM\n";
        echo "2. Switch to 'Shop-Specific' mode (select shop from dropdown)\n";
        echo "3. Enter shop-specific data\n";
        echo "4. Click 'Save'\n";
        echo "5. This will create product_shop_data record with sync_status='pending'\n";
        echo "6. Then sync job can be triggered\n\n";
    } else {
        echo "✓ Total product-shop assignments: {$assignmentsCount}\n\n";

        echo "Last 5 assignments:\n";
        $assignments = DB::table('product_shop_data')
            ->select([
                'id',
                'product_id',
                'shop_id',
                'sync_status',
                'is_published',
                'prestashop_product_id',
                'last_success_sync_at',
                'created_at'
            ])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        foreach ($assignments as $assignment) {
            echo "  - Product ID: {$assignment->product_id} → Shop ID: {$assignment->shop_id}\n";
            echo "    Status: {$assignment->sync_status}\n";
            echo "    Published: " . ($assignment->is_published ? 'YES' : 'NO') . "\n";
            echo "    PrestaShop ID: " . ($assignment->prestashop_product_id ?? 'NULL') . "\n";
            echo "    Last Sync: " . ($assignment->last_success_sync_at ?? 'NEVER') . "\n";
            echo "    Created: {$assignment->created_at}\n";
            echo "\n";
        }
    }

    // Step 4: Check if there are products WITHOUT shop assignments
    if ($productsCount > 0 && $assignmentsCount < $productsCount) {
        $unassignedCount = $productsCount - $assignmentsCount;
        echo "⚠️ WARNING: {$unassignedCount} products are NOT assigned to any shop!\n";
        echo "These products will NOT be synchronized.\n\n";

        echo "Products without shop assignments:\n";
        $unassigned = DB::table('products')
            ->select(['id', 'sku', 'name'])
            ->whereNotIn('id', function ($query) {
                $query->select('product_id')
                    ->from('product_shop_data');
            })
            ->limit(10)
            ->get();

        foreach ($unassigned as $product) {
            echo "  - [ID: {$product->id}] {$product->name} (SKU: {$product->sku})\n";
        }
        echo "\n";
    }

    echo "=== DIAGNOSTICS COMPLETE ===\n\n";

    // Summary
    echo "SUMMARY:\n";
    echo "  Products: {$productsCount}\n";
    echo "  Shops: {$shopsCount}\n";
    echo "  Product-Shop Assignments: {$assignmentsCount}\n";

    if ($assignmentsCount === 0) {
        echo "\n❌ ROOT CAUSE: No products are assigned to shops!\n";
        echo "Action required: Assign products to shops using 'Shop-Specific' mode.\n";
    } elseif ($assignmentsCount > 0) {
        echo "\n✓ Products are assigned to shops.\n";
        echo "If sync is stuck, check sync_jobs table or Laravel queue worker.\n";
    }

} catch (Exception $e) {
    echo "❌ ERROR: {$e->getMessage()}\n";
    echo "Trace:\n{$e->getTraceAsString()}\n";
    exit(1);
}
