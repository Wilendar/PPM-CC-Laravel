<?php
/**
 * ANALYSIS: Category Tables Duplication Issue
 *
 * Purpose: Analyze data in product_shop_categories vs product_categories
 * to understand scope of migration and detect overlaps
 *
 * Date: 2025-11-19
 */

echo "=== CATEGORY TABLES DUPLICATION ANALYSIS ===\n\n";

// 1. Check product_shop_categories (OLD table - created 2025-09-22)
echo "1. TABLE: product_shop_categories (DEPRECATED)\n";
echo str_repeat("-", 60) . "\n";

$oldTableCount = DB::table('product_shop_categories')->count();
echo "Total records: {$oldTableCount}\n";

if ($oldTableCount > 0) {
    $oldTableSample = DB::table('product_shop_categories')
        ->select('product_id', 'shop_id', 'category_id', 'is_primary', 'sort_order')
        ->limit(5)
        ->get();

    echo "\nSample records (first 5):\n";
    foreach ($oldTableSample as $record) {
        echo sprintf(
            "  Product: %d, Shop: %d, Category: %d, Primary: %s, Sort: %d\n",
            $record->product_id,
            $record->shop_id,
            $record->category_id,
            $record->is_primary ? 'YES' : 'NO',
            $record->sort_order
        );
    }

    // Check unique products and shops
    $uniqueProducts = DB::table('product_shop_categories')
        ->distinct('product_id')
        ->count('product_id');
    $uniqueShops = DB::table('product_shop_categories')
        ->distinct('shop_id')
        ->count('shop_id');

    echo "\nUnique products: {$uniqueProducts}\n";
    echo "Unique shops: {$uniqueShops}\n";
}

echo "\n";

// 2. Check product_categories with shop_id NOT NULL (NEW architecture - added 2025-10-13)
echo "2. TABLE: product_categories (shop_id NOT NULL - per-shop)\n";
echo str_repeat("-", 60) . "\n";

$newPerShopCount = DB::table('product_categories')
    ->whereNotNull('shop_id')
    ->count();
echo "Total per-shop records: {$newPerShopCount}\n";

if ($newPerShopCount > 0) {
    $newPerShopSample = DB::table('product_categories')
        ->select('product_id', 'shop_id', 'category_id', 'is_primary', 'sort_order')
        ->whereNotNull('shop_id')
        ->limit(5)
        ->get();

    echo "\nSample records (first 5):\n";
    foreach ($newPerShopSample as $record) {
        echo sprintf(
            "  Product: %d, Shop: %d, Category: %d, Primary: %s, Sort: %d\n",
            $record->product_id,
            $record->shop_id,
            $record->category_id,
            $record->is_primary ? 'YES' : 'NO',
            $record->sort_order
        );
    }
}

echo "\n";

// 3. Check product_categories with shop_id NULL (default categories)
echo "3. TABLE: product_categories (shop_id NULL - default)\n";
echo str_repeat("-", 60) . "\n";

$defaultCount = DB::table('product_categories')
    ->whereNull('shop_id')
    ->count();
echo "Total default records: {$defaultCount}\n";

if ($defaultCount > 0) {
    $defaultSample = DB::table('product_categories')
        ->select('product_id', 'category_id', 'is_primary', 'sort_order')
        ->whereNull('shop_id')
        ->limit(5)
        ->get();

    echo "\nSample records (first 5):\n";
    foreach ($defaultSample as $record) {
        echo sprintf(
            "  Product: %d, Shop: NULL, Category: %d, Primary: %s, Sort: %d\n",
            $record->product_id,
            $record->category_id,
            $record->is_primary ? 'YES' : 'NO',
            $record->sort_order
        );
    }
}

echo "\n";

// 4. Check for OVERLAPS (same product + shop + category in both tables)
echo "4. OVERLAP DETECTION\n";
echo str_repeat("-", 60) . "\n";

if ($oldTableCount > 0 && $newPerShopCount > 0) {
    $overlaps = DB::table('product_shop_categories as psc')
        ->join('product_categories as pc', function($join) {
            $join->on('psc.product_id', '=', 'pc.product_id')
                 ->on('psc.shop_id', '=', 'pc.shop_id')
                 ->on('psc.category_id', '=', 'pc.category_id');
        })
        ->whereNotNull('pc.shop_id')
        ->select(
            'psc.product_id',
            'psc.shop_id',
            'psc.category_id',
            'psc.is_primary as old_is_primary',
            'pc.is_primary as new_is_primary',
            'psc.sort_order as old_sort',
            'pc.sort_order as new_sort'
        )
        ->get();

    $overlapCount = $overlaps->count();
    echo "Overlapping records: {$overlapCount}\n";

    if ($overlapCount > 0) {
        echo "\nSample overlaps (first 5):\n";
        foreach ($overlaps->take(5) as $overlap) {
            echo sprintf(
                "  Product: %d, Shop: %d, Category: %d\n",
                $overlap->product_id,
                $overlap->shop_id,
                $overlap->category_id
            );
            echo sprintf(
                "    OLD: Primary=%s, Sort=%d | NEW: Primary=%s, Sort=%d\n",
                $overlap->old_is_primary ? 'YES' : 'NO',
                $overlap->old_sort,
                $overlap->new_is_primary ? 'YES' : 'NO',
                $overlap->new_sort
            );
        }
    }
} else {
    echo "No overlaps possible (one or both tables empty)\n";
}

echo "\n";

// 5. Check which table is ACTIVELY USED
echo "5. ACTIVE USAGE DETECTION\n";
echo str_repeat("-", 60) . "\n";

// Check recent updates in both tables
$oldTableRecent = DB::table('product_shop_categories')
    ->where('updated_at', '>=', now()->subDays(7))
    ->count();
$newTableRecent = DB::table('product_categories')
    ->whereNotNull('shop_id')
    ->where('updated_at', '>=', now()->subDays(7))
    ->count();

echo "Records updated in last 7 days:\n";
echo "  product_shop_categories: {$oldTableRecent}\n";
echo "  product_categories (per-shop): {$newTableRecent}\n";

echo "\n";

// 6. SUMMARY & RECOMMENDATIONS
echo "=== SUMMARY ===\n";
echo str_repeat("=", 60) . "\n";
echo "product_shop_categories (OLD): {$oldTableCount} records\n";
echo "product_categories (per-shop): {$newPerShopCount} records\n";
echo "product_categories (default): {$defaultCount} records\n";
echo "Overlaps: " . ($overlapCount ?? 0) . " records\n";
echo "\n";

echo "RECOMMENDATION:\n";
if ($oldTableCount > 0 && $newPerShopCount == 0) {
    echo "  ✅ SAFE TO MIGRATE: All data in OLD table, NEW table empty\n";
    echo "  → Simple migration from product_shop_categories to product_categories\n";
} elseif ($oldTableCount > 0 && $newPerShopCount > 0 && ($overlapCount ?? 0) == 0) {
    echo "  ⚠️ CAREFUL MERGE: Both tables have data, NO overlaps\n";
    echo "  → Migrate OLD records to NEW table (no conflicts)\n";
} elseif (($overlapCount ?? 0) > 0) {
    echo "  🚨 CONFLICT RESOLUTION: Overlapping records detected!\n";
    echo "  → Decide which table wins (NEW recommended)\n";
    echo "  → Migration must handle conflicts\n";
} else {
    echo "  ✅ CLEAN STATE: OLD table empty\n";
    echo "  → Just drop the table, no migration needed\n";
}

echo "\n";
echo "=== END ANALYSIS ===\n";
