<?php

/**
 * Category Status Fix Verification Script
 *
 * ETAP_07b FAZA 2: Verify cache invalidation and status detection fixes
 *
 * Tests:
 * 1. Set identical categories in shop as default → Should show 'same' status
 * 2. Verify CategoryValidatorService returns 'zgodne'
 * 3. Check debug logs for comparison details
 *
 * Usage: php artisan tinker --execute="require '_TEMP/test_category_status_fix.php';"
 */

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\CategoryValidatorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "🧪 CATEGORY STATUS FIX VERIFICATION - ETAP_07b FAZA 2\n";
echo str_repeat('=', 70) . "\n\n";

try {
    // Get test product with categories
    $product = Product::whereHas('categories')->first();

    if (!$product) {
        echo "❌ No products with categories found\n";
        exit(1);
    }

    echo "📦 Test Product: {$product->sku} - {$product->name}\n";
    echo "   Product ID: {$product->id}\n\n";

    // Get default categories
    $defaultCategories = $product->categories()->pluck('categories.id')->toArray();
    $defaultPrimary = $product->primaryCategory()->first()?->id;

    echo "📂 Default Categories: [" . implode(', ', $defaultCategories) . "]\n";
    echo "   Primary: " . ($defaultPrimary ?? 'None') . "\n\n";

    // Get active shop
    $shop = PrestaShopShop::where('is_active', true)->first();

    if (!$shop) {
        echo "❌ No active shops found\n";
        exit(1);
    }

    echo "🏪 Test Shop: {$shop->name} (ID: {$shop->id})\n\n";

    echo str_repeat('-', 70) . "\n";
    echo "TEST 1: Set IDENTICAL categories in shop as default\n";
    echo str_repeat('-', 70) . "\n\n";

    // Clear existing shop categories
    DB::table('product_categories')
        ->where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->delete();

    // Add shop categories IDENTICAL to default
    foreach ($defaultCategories as $catId) {
        DB::table('product_categories')->insert([
            'product_id' => $product->id,
            'category_id' => $catId,
            'shop_id' => $shop->id,
            'is_primary' => $catId === $defaultPrimary,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    echo "✅ Inserted identical categories for shop {$shop->id}\n";
    echo "   Shop categories: [" . implode(', ', $defaultCategories) . "]\n";
    echo "   Primary: " . ($defaultPrimary ?? 'None') . "\n\n";

    // Test CategoryValidatorService
    $validator = app(CategoryValidatorService::class);
    $comparison = $validator->compareWithDefault($product, $shop->id);

    echo "📊 CategoryValidatorService Result:\n";
    echo "   Status: {$comparison['status']}\n";
    echo "   Expected: 'zgodne' (STATUS_IDENTICAL)\n";

    if ($comparison['status'] === 'zgodne') {
        echo "   ✅ PASSED: Status is 'zgodne'\n";
    } else {
        echo "   ❌ FAILED: Expected 'zgodne', got '{$comparison['status']}'\n";
        echo "   Diff: " . json_encode($comparison['diff']) . "\n";
    }

    echo "\n";

    // Test database comparison (reload from DB to ensure fresh data)
    $product->refresh();
    $shopCategoriesDB = $product->categoriesForShop($shop->id, false)
        ->pluck('categories.id')
        ->sort(SORT_NUMERIC)
        ->values()
        ->toArray();
    $defaultCategoriesDB = $product->categories()
        ->pluck('categories.id')
        ->sort(SORT_NUMERIC)
        ->values()
        ->toArray();

    echo "📊 Database Comparison:\n";
    echo "   Shop categories (DB): [" . implode(', ', $shopCategoriesDB) . "]\n";
    echo "   Default categories (DB): [" . implode(', ', $defaultCategoriesDB) . "]\n";
    echo "   Are identical: " . ($shopCategoriesDB === $defaultCategoriesDB ? 'YES ✅' : 'NO ❌') . "\n";

    if ($shopCategoriesDB === $defaultCategoriesDB) {
        echo "   ✅ PASSED: Arrays are identical after numeric sort\n";
    } else {
        echo "   ❌ FAILED: Arrays differ\n";
        echo "   Types - Shop: [" . implode(', ', array_map('gettype', $shopCategoriesDB)) . "]\n";
        echo "   Types - Default: [" . implode(', ', array_map('gettype', $defaultCategoriesDB)) . "]\n";
    }

    echo "\n" . str_repeat('-', 70) . "\n";
    echo "TEST 2: Check debug logs (last 10 CATEGORY STATUS COMPARISON entries)\n";
    echo str_repeat('-', 70) . "\n\n";

    echo "📝 Recent debug logs should show comparison details.\n";
    echo "   Check: storage/logs/laravel.log\n";
    echo "   Search for: 'CATEGORY STATUS COMPARISON'\n\n";

    // Cleanup: Remove shop categories
    DB::table('product_categories')
        ->where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->delete();

    echo "🧹 Cleanup: Shop categories removed\n\n";

    echo str_repeat('=', 70) . "\n";
    echo "TEST SUMMARY\n";
    echo str_repeat('=', 70) . "\n\n";

    $allPassed = ($comparison['status'] === 'zgodne') && ($shopCategoriesDB === $defaultCategoriesDB);

    if ($allPassed) {
        echo "✅ ALL TESTS PASSED\n";
        echo "\nFIXES VERIFIED:\n";
        echo "1. ✅ CategoryValidatorService correctly detects identical categories\n";
        echo "2. ✅ Database comparison works with numeric sort\n";
        echo "3. ✅ No type mismatches or comparison bugs\n";
    } else {
        echo "❌ SOME TESTS FAILED\n";
        echo "\nREQUIRED ACTIONS:\n";
        if ($comparison['status'] !== 'zgodne') {
            echo "- Fix CategoryValidatorService::compareWithDefault()\n";
        }
        if ($shopCategoriesDB !== $defaultCategoriesDB) {
            echo "- Fix database query or sort logic\n";
        }
    }

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ VERIFICATION COMPLETED\n";
