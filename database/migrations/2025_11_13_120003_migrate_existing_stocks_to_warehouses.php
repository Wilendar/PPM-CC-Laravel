<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migration: Migrate Existing Stocks to Warehouses (Strategy B - Complex)
 *
 * CRITICAL Data Migration:
 * - Multi-pass migration (ZERO data loss)
 * - Step 1: Migrate global stocks (no shop_id) → warehouse_id = MPPTRADE
 * - Step 2: PRESERVE shop-specific stocks (shop_id NOT NULL)
 * - Step 3: Verification queries
 *
 * Strategy B Logic:
 * - Global stocks (shop_id = NULL) → Assign to MPPTRADE warehouse
 * - Shop-specific stocks (shop_id NOT NULL) → PRESERVE as-is (shop overrides)
 *
 * Safety:
 * - NO data deletion
 * - Rollback support
 * - Comprehensive logging
 * - Validation queries
 *
 * @package Database\Migrations
 * @version Strategy B - Complex Warehouse Redesign
 * @since 2025-11-13
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Multi-pass stock migration with data preservation
     */
    public function up(): void
    {
        Log::info('Starting Strategy B stock migration (ZERO data loss)');

        // Step 1: Get MPPTRADE warehouse ID
        $mpptrade = DB::table('warehouses')->where('code', 'mpptrade')->first();

        if (!$mpptrade) {
            $message = "CRITICAL: MPPTRADE warehouse not found! Run WarehouseSeeder first.";
            Log::error($message);
            throw new \Exception($message);
        }

        Log::info("Found MPPTRADE warehouse", ['id' => $mpptrade->id, 'name' => $mpptrade->name]);

        // Step 2: Count existing stocks BEFORE migration
        $totalStocks = DB::table('product_stock')->count();
        $globalStocks = DB::table('product_stock')->whereNull('shop_id')->count();
        $shopSpecificStocks = DB::table('product_stock')->whereNotNull('shop_id')->count();

        Log::info('Stock inventory BEFORE migration', [
            'total' => $totalStocks,
            'global_stocks' => $globalStocks,
            'shop_specific_stocks' => $shopSpecificStocks,
        ]);

        // Step 3: Migrate GLOBAL stocks (no shop_id) → warehouse_id = MPPTRADE
        // These are stocks without shop-specific overrides
        $migratedCount = DB::table('product_stock')
            ->whereNull('shop_id')
            ->whereNull('warehouse_id') // Only migrate if warehouse_id not already set
            ->update(['warehouse_id' => $mpptrade->id]);

        Log::info("Migrated global stocks to MPPTRADE", ['count' => $migratedCount]);

        // Step 4: **CRITICAL** - PRESERVE shop-specific stocks
        // NO CHANGES to records where shop_id IS NOT NULL
        // These remain as shop overrides (warehouse_id = NULL, shop_id = X)
        Log::info("Preserving shop-specific stocks (NO CHANGES)", ['count' => $shopSpecificStocks]);

        // Step 5: Verification queries
        $verificationResults = [
            'total_stocks_after' => DB::table('product_stock')->count(),
            'global_stocks_with_warehouse' => DB::table('product_stock')
                ->whereNotNull('warehouse_id')
                ->whereNull('shop_id')
                ->count(),
            'shop_specific_preserved' => DB::table('product_stock')
                ->whereNull('warehouse_id')
                ->whereNotNull('shop_id')
                ->count(),
            'invalid_state_both_set' => DB::table('product_stock')
                ->whereNotNull('warehouse_id')
                ->whereNotNull('shop_id')
                ->count(),
            'orphaned_stocks' => DB::table('product_stock')
                ->whereNull('warehouse_id')
                ->whereNull('shop_id')
                ->count(),
        ];

        Log::info('Stock migration verification', $verificationResults);

        // Step 6: Validate results
        if ($verificationResults['total_stocks_after'] !== $totalStocks) {
            $message = "CRITICAL: Data loss detected! Stocks before: {$totalStocks}, after: {$verificationResults['total_stocks_after']}";
            Log::error($message);
            throw new \Exception($message);
        }

        if ($verificationResults['invalid_state_both_set'] > 0) {
            $message = "CRITICAL: Invalid state detected! Found {$verificationResults['invalid_state_both_set']} stocks with both warehouse_id AND shop_id set.";
            Log::error($message);
            throw new \Exception($message);
        }

        // Success summary
        $summary = [
            'migration_status' => 'SUCCESS',
            'total_stocks' => $totalStocks,
            'migrated_to_mpptrade' => $migratedCount,
            'shop_specific_preserved' => $verificationResults['shop_specific_preserved'],
            'orphaned_stocks' => $verificationResults['orphaned_stocks'],
            'data_loss' => 'ZERO',
        ];

        Log::info('Strategy B stock migration completed successfully', $summary);

        // Output to console
        echo "\n";
        echo "✅ Strategy B Stock Migration Summary:\n";
        echo "   - Total stocks: {$totalStocks}\n";
        echo "   - Migrated to MPPTRADE: {$migratedCount}\n";
        echo "   - Shop-specific preserved: {$verificationResults['shop_specific_preserved']}\n";
        echo "   - Orphaned stocks: {$verificationResults['orphaned_stocks']}\n";
        echo "   - Data loss: ZERO ✅\n";
        echo "\n";
    }

    /**
     * Reverse the migrations.
     *
     * Rollback: Clear warehouse_id assignments (restore to original state)
     */
    public function down(): void
    {
        Log::info('Rolling back Strategy B stock migration');

        // Count stocks BEFORE rollback
        $beforeRollback = [
            'with_warehouse' => DB::table('product_stock')->whereNotNull('warehouse_id')->count(),
            'with_shop' => DB::table('product_stock')->whereNotNull('shop_id')->count(),
        ];

        Log::info('Stock state BEFORE rollback', $beforeRollback);

        // Clear warehouse_id from global stocks (restore to original state)
        $clearedCount = DB::table('product_stock')
            ->whereNull('shop_id') // Only clear global stocks
            ->update(['warehouse_id' => null]);

        Log::info("Cleared warehouse_id from global stocks", ['count' => $clearedCount]);

        // Verify shop-specific stocks untouched
        $afterRollback = [
            'with_warehouse' => DB::table('product_stock')->whereNotNull('warehouse_id')->count(),
            'with_shop' => DB::table('product_stock')->whereNotNull('shop_id')->count(),
        ];

        Log::info('Stock state AFTER rollback', $afterRollback);

        if ($afterRollback['with_shop'] !== $beforeRollback['with_shop']) {
            $message = "ROLLBACK ERROR: Shop-specific stocks changed! Before: {$beforeRollback['with_shop']}, After: {$afterRollback['with_shop']}";
            Log::error($message);
            throw new \Exception($message);
        }

        Log::info('Strategy B stock migration rollback completed successfully');

        echo "\n";
        echo "✅ Rollback Summary:\n";
        echo "   - Cleared warehouse_id: {$clearedCount}\n";
        echo "   - Shop-specific stocks preserved: {$afterRollback['with_shop']}\n";
        echo "\n";
    }
};
