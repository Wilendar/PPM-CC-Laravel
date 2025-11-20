<?php

/**
 * PrestaShop Categories Fetch Tool - Product #1831 (Standalone)
 *
 * ETAP_07b FAZA 1 - Test pobierania kategorii z PrestaShop (bez bazy PPM)
 *
 * Usage:
 * Upload to produkcja i uruchom: php _TOOLS/test_prestashop_categories_1831.php
 *
 * This tool works WITHOUT Laravel dependencies - direct PrestaShop API test
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  PrestaShop Categories Test - Product #1831 (Standalone)         ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";

// PrestaShop B2B Test DEV connection
$shopUrl = 'https://test.sklep-b2b.mpptrade.pl';
$apiKey = 'HHQB84EIQZC3GCAJX2VPCHZUABBLLDKV'; // From dane_hostingu.md
$productId = 1831;

echo "[STEP 1] Connection details:\n";
echo "   URL: {$shopUrl}\n";
echo "   API Key: " . substr($apiKey, 0, 10) . "...\n";
echo "   Product ID: {$productId}\n";
echo "\n";

// STEP 2: Fetch product #1831
echo "[STEP 2] Fetching product #{$productId} from PrestaShop API...\n";

$productUrl = "{$shopUrl}/api/products/{$productId}?output_format=JSON";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $productUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, "{$apiKey}:");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode !== 200) {
    echo "❌ ERROR: HTTP {$httpCode}\n";
    if ($error) {
        echo "   CURL Error: {$error}\n";
    }
    echo "   Response: {$response}\n";
    exit(1);
}

$productData = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "❌ ERROR: Invalid JSON response\n";
    echo "   Response: {$response}\n";
    exit(1);
}

if (!isset($productData['product'])) {
    echo "❌ ERROR: Invalid product data structure\n";
    echo "   Response:\n";
    echo json_encode($productData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

$product = $productData['product'];

$productName = is_array($product['name']) ? $product['name'][0]['value'] : $product['name'];
$productSku = $product['reference'] ?? 'N/A';

echo "✅ Product fetched:\n";
echo "   ID: {$productId}\n";
echo "   Name: {$productName}\n";
echo "   SKU: {$productSku}\n";
echo "\n";

// STEP 3: Extract category IDs
echo "[STEP 3] Extracting category IDs from product...\n";

$categoryIds = [];

if (isset($product['associations']['categories'])) {
    foreach ($product['associations']['categories'] as $cat) {
        if (is_array($cat) && isset($cat['id'])) {
            $categoryIds[] = (int) $cat['id'];
        } elseif (is_numeric($cat)) {
            $categoryIds[] = (int) $cat;
        }
    }
}

if (empty($categoryIds)) {
    echo "❌ ERROR: No categories found for product #{$productId}\n";
    echo "   Product associations:\n";
    echo json_encode($product['associations'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    exit(1);
}

echo "✅ Found " . count($categoryIds) . " categories: " . implode(', ', $categoryIds) . "\n";
echo "\n";

// STEP 4: Fetch category details
echo "[STEP 4] Fetching category details from PrestaShop API...\n";
echo "────────────────────────────────────────────────────────────────────\n";

$categoryTree = [];

foreach ($categoryIds as $catId) {
    $categoryUrl = "{$shopUrl}/api/categories/{$catId}?output_format=JSON";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $categoryUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, "{$apiKey}:");
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "⚠️  WARNING: Failed to fetch category {$catId} (HTTP {$httpCode})\n";
        if ($error) {
            echo "   CURL Error: {$error}\n";
        }
        continue;
    }

    $catData = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE || !isset($catData['category'])) {
        echo "⚠️  WARNING: Invalid category {$catId} response\n";
        continue;
    }

    $cat = $catData['category'];

    $catName = is_array($cat['name']) ? $cat['name'][0]['value'] : ($cat['name'] ?? 'N/A');
    $catParentId = isset($cat['id_parent']) ? (int) $cat['id_parent'] : null;
    $catLevel = isset($cat['level_depth']) ? (int) $cat['level_depth'] : 0;

    $categoryTree[$catId] = [
        'id' => $catId,
        'name' => $catName,
        'parent_id' => $catParentId,
        'level' => $catLevel,
    ];

    $indent = str_repeat('  ', $catLevel);
    $parentInfo = $catParentId ? " (Parent: {$catParentId})" : " (Root)";

    echo "{$indent}[{$catId}] {$catName}{$parentInfo}\n";
}

echo "────────────────────────────────────────────────────────────────────\n";
echo "\n";

// STEP 5: Build hierarchy tree
echo "[STEP 5] Building category hierarchy tree...\n";
echo "────────────────────────────────────────────────────────────────────\n";

// Funkcja do wyświetlania drzewa
function displayTree($categories, $parentId = null, $level = 0) {
    $found = false;

    foreach ($categories as $cat) {
        if ($cat['parent_id'] == $parentId || ($parentId === null && $cat['parent_id'] === null)) {
            $indent = str_repeat('  ', $level);
            $arrow = $level > 0 ? '└─ ' : '';

            echo "{$indent}{$arrow}[{$cat['id']}] {$cat['name']}\n";

            $found = true;

            // Rekursywnie wyświetl dzieci
            displayTree($categories, $cat['id'], $level + 1);
        }
    }

    return $found;
}

// Znajdź root categories (te które mają parent_id 1 lub 2 - PrestaShop roots)
$rootCategories = array_filter($categoryTree, function($cat) {
    return in_array($cat['parent_id'], [1, 2], true);
});

if (empty($rootCategories)) {
    echo "⚠️  No root categories found (expected parent_id = 1 or 2)\n";
    echo "   Displaying all categories:\n";
    foreach ($categoryTree as $cat) {
        echo "   [{$cat['id']}] {$cat['name']} (Parent: {$cat['parent_id']})\n";
    }
} else {
    // Wyświetl drzewo zaczynając od root
    foreach ($rootCategories as $rootCat) {
        echo "[{$rootCat['id']}] {$rootCat['name']} (Root)\n";
        displayTree($categoryTree, $rootCat['id'], 1);
    }
}

echo "────────────────────────────────────────────────────────────────────\n";
echo "\n";

// STEP 6: Summary
echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║                         SUMMARY                                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n";
echo "\n";
echo "📊 Statistics:\n";
echo "   Product ID: {$productId}\n";
echo "   Product Name: {$productName}\n";
echo "   Product SKU: {$productSku}\n";
echo "   Total categories: " . count($categoryIds) . "\n";
echo "   Categories fetched: " . count($categoryTree) . "\n";
echo "   Root categories (parent 1 or 2): " . count($rootCategories) . "\n";
echo "\n";

echo "📋 Category IDs for fromPrestaShopFormat():\n";
echo "   [" . implode(', ', $categoryIds) . "]\n";
echo "\n";

echo "🎯 Test completed successfully!\n";
echo "\n";
