<?php

/**
 * Category Validator Service Test Script
 *
 * ETAP_07b FAZA 2: Category Validator Testing
 *
 * Tests 3 scenarios:
 * 1. Product with identical categories → "zgodne" (green)
 * 2. Product with different categories → "własne" (blue)
 * 3. Product with no shop categories → "dziedziczone" (gray)
 *
 * Usage: php artisan tinker --execute="require '_TEMP/test_category_validator_faza2.php';"
 */

use App\Models\Product;
use App\Models\PrestaShopShop;
use App\Services\CategoryValidatorService;
use Illuminate\Support\Facades\DB;

echo "🧪 CATEGORY VALIDATOR SERVICE TEST - ETAP_07b FAZA 2\n";
echo str_repeat('=', 70) . "\n\n";

try {
    // Get service instance
    $validator = app(CategoryValidatorService::class);
    echo "✅ CategoryValidatorService loaded\n\n";

    // Get test product (first product with categories)
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

    // Get active shop for testing
    $shop = PrestaShopShop::where('is_active', true)->first();

    if (!$shop) {
        echo "❌ No active shops found\n";
        exit(1);
    }

    echo "🏪 Test Shop: {$shop->name} (ID: {$shop->id})\n\n";

    echo str_repeat('-', 70) . "\n";
    echo "SCENARIO 1: Product with IDENTICAL categories (should be 'zgodne')\n";
    echo str_repeat('-', 70) . "\n\n";

    // Test scenario 1: Create shop categories identical to default
    // Clear existing shop categories first
    DB::table('product_categories')
        ->where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->delete();

    // Add shop categories identical to default
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

    // Test validation
    $result1 = $validator->compareWithDefault($product, $shop->id);
    $badge1 = $validator->getStatusBadge($result1['status']);

    echo "Status: {$result1['status']}\n";
    echo "Badge: {$badge1['text']} ({$badge1['color']})\n";
    echo "Description: {$badge1['description']}\n";
    echo "Diff: " . (empty($result1['diff']) ? 'None (identical)' : json_encode($result1['diff'])) . "\n";

    if ($result1['status'] === CategoryValidatorService::STATUS_IDENTICAL) {
        echo "✅ PASSED: Status is 'zgodne' as expected\n";
    } else {
        echo "❌ FAILED: Expected 'zgodne', got '{$result1['status']}'\n";
    }

    echo "\n" . str_repeat('-', 70) . "\n";
    echo "SCENARIO 2: Product with DIFFERENT categories (should be 'własne')\n";
    echo str_repeat('-', 70) . "\n\n";

    // Test scenario 2: Modify shop categories to be different
    DB::table('product_categories')
        ->where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->delete();

    // Make categories different by:
    // 1. Removing first default category (to create "removed" diff)
    // 2. Changing primary category (to create "primary_changed" diff)
    $differentCategories = array_slice($defaultCategories, 1); // Remove first category
    $newPrimary = $differentCategories[1] ?? $differentCategories[0]; // Change primary to second category

    foreach ($differentCategories as $catId) {
        DB::table('product_categories')->insert([
            'product_id' => $product->id,
            'category_id' => $catId,
            'shop_id' => $shop->id,
            'is_primary' => $catId === $newPrimary,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Test validation
    $result2 = $validator->compareWithDefault($product, $shop->id);
    $badge2 = $validator->getStatusBadge($result2['status']);
    $tooltip2 = $validator->getDiffTooltip($result2['diff'], $product);

    echo "Status: {$result2['status']}\n";
    echo "Badge: {$badge2['text']} ({$badge2['color']})\n";
    echo "Description: {$badge2['description']}\n";
    echo "Diff:\n";
    echo "  Added: [" . implode(', ', $result2['diff']['added'] ?? []) . "]\n";
    echo "  Removed: [" . implode(', ', $result2['diff']['removed'] ?? []) . "]\n";
    echo "  Primary Changed: " . ($result2['diff']['primary_changed'] ? 'Yes' : 'No') . "\n";
    echo "Tooltip: " . ($tooltip2 ?? 'None') . "\n";

    if ($result2['status'] === CategoryValidatorService::STATUS_CUSTOM) {
        echo "✅ PASSED: Status is 'własne' as expected\n";
    } else {
        echo "❌ FAILED: Expected 'własne', got '{$result2['status']}'\n";
    }

    echo "\n" . str_repeat('-', 70) . "\n";
    echo "SCENARIO 3: Product with NO shop categories (should be 'dziedziczone')\n";
    echo str_repeat('-', 70) . "\n\n";

    // Test scenario 3: Remove all shop categories
    DB::table('product_categories')
        ->where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->delete();

    // Test validation
    $result3 = $validator->compareWithDefault($product, $shop->id);
    $badge3 = $validator->getStatusBadge($result3['status']);

    echo "Status: {$result3['status']}\n";
    echo "Badge: {$badge3['text']} ({$badge3['color']})\n";
    echo "Description: {$badge3['description']}\n";
    echo "Diff: " . (empty($result3['diff']) ? 'None (inherits from default)' : json_encode($result3['diff'])) . "\n";

    if ($result3['status'] === CategoryValidatorService::STATUS_INHERITED) {
        echo "✅ PASSED: Status is 'dziedziczone' as expected\n";
    } else {
        echo "❌ FAILED: Expected 'dziedziczone', got '{$result3['status']}'\n";
    }

    echo "\n" . str_repeat('=', 70) . "\n";
    echo "TEST SUMMARY\n";
    echo str_repeat('=', 70) . "\n\n";

    $passed = 0;
    $failed = 0;

    if ($result1['status'] === CategoryValidatorService::STATUS_IDENTICAL) $passed++; else $failed++;
    if ($result2['status'] === CategoryValidatorService::STATUS_CUSTOM) $passed++; else $failed++;
    if ($result3['status'] === CategoryValidatorService::STATUS_INHERITED) $passed++; else $failed++;

    echo "Total Tests: 3\n";
    echo "Passed: {$passed}\n";
    echo "Failed: {$failed}\n\n";

    if ($failed === 0) {
        echo "✅ ALL TESTS PASSED\n";
    } else {
        echo "❌ SOME TESTS FAILED\n";
    }

    // Cleanup: Restore original state (remove shop categories)
    DB::table('product_categories')
        ->where('product_id', $product->id)
        ->where('shop_id', $shop->id)
        ->delete();

    echo "\n🧹 Cleanup: Shop categories removed (restored to original state)\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✅ TEST COMPLETED\n";
