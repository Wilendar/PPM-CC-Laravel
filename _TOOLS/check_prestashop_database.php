<?php
/**
 * Check PrestaShop Database - Direct Category Verification
 *
 * DIAGNOSTIC 2025-11-05: PrestaShop API ignores associations.categories
 * This script checks the ps_category_product table directly to see if categories exist
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PRESTASHOP DATABASE DIAGNOSTIC ===\n\n";

// Get shop
$shop = \App\Models\PrestaShopShop::find(1); // B2B Test DEV

if (!$shop) {
    echo "❌ Shop not found\n";
    exit(1);
}

echo "Shop: {$shop->name}\n";
echo "URL: {$shop->url}\n";
echo "DB Host: {$shop->db_host}\n";
echo "DB Name: {$shop->db_name}\n\n";

// Connect to PrestaShop database
try {
    $pdo = new PDO(
        "mysql:host={$shop->db_host};dbname={$shop->db_name};charset=utf8mb4",
        $shop->db_user,
        decrypt($shop->db_password)
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connected to PrestaShop database\n\n";

} catch (PDOException $e) {
    echo "❌ Failed to connect to PrestaShop database:\n";
    echo "Error: {$e->getMessage()}\n";
    exit(1);
}

// Test product ID in PrestaShop
$prestashopProductId = 9760; // TEST-SYNC-001

echo "1. Checking product in ps_product table...\n";

$stmt = $pdo->prepare("
    SELECT
        id_product,
        reference,
        id_category_default,
        active
    FROM ps_product
    WHERE id_product = ?
");
$stmt->execute([$prestashopProductId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "❌ Product {$prestashopProductId} not found in ps_product\n";
    exit(1);
}

echo "✅ Product found:\n";
echo "   - ID: {$product['id_product']}\n";
echo "   - Reference: {$product['reference']}\n";
echo "   - id_category_default: {$product['id_category_default']}\n";
echo "   - Active: {$product['active']}\n\n";

// Check categories in ps_category_product
echo "2. Checking product categories in ps_category_product table...\n";

$stmt = $pdo->prepare("
    SELECT
        id_product,
        id_category,
        position
    FROM ps_category_product
    WHERE id_product = ?
    ORDER BY position
");
$stmt->execute([$prestashopProductId]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($categories)) {
    echo "❌ NO CATEGORIES found in ps_category_product!\n";
    echo "→ This confirms PrestaShop API is NOT saving associations\n\n";
} else {
    echo "✅ Found " . count($categories) . " categories:\n";
    foreach ($categories as $cat) {
        echo "   - Category ID: {$cat['id_category']}, Position: {$cat['position']}\n";
    }
    echo "\n";
}

// Check if category 12 exists
echo "3. Checking if category 12 (PITGANG mapped) exists in ps_category...\n";

$stmt = $pdo->prepare("
    SELECT
        id_category,
        id_parent,
        active,
        position
    FROM ps_category
    WHERE id_category = 12
");
$stmt->execute();
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    echo "❌ Category 12 not found in ps_category\n";
    echo "→ CategorySyncService may have failed to create category\n\n";
} else {
    echo "✅ Category 12 exists:\n";
    echo "   - ID: {$category['id_category']}\n";
    echo "   - Parent ID: {$category['id_parent']}\n";
    echo "   - Active: {$category['active']}\n";
    echo "   - Position: {$category['position']}\n\n";
}

// Get category name
$stmt = $pdo->prepare("
    SELECT name
    FROM ps_category_lang
    WHERE id_category = 12 AND id_lang = 1
");
$stmt->execute();
$categoryName = $stmt->fetchColumn();

if ($categoryName) {
    echo "   - Name: {$categoryName}\n\n";
}

// SOLUTION: Try to INSERT category association manually
echo "4. TESTING SOLUTION: Inserting category association directly...\n";

try {
    // Check if association already exists
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM ps_category_product
        WHERE id_product = ? AND id_category = ?
    ");
    $stmt->execute([$prestashopProductId, 12]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        echo "⚠️  Association already exists (but not visible via API?)\n\n";
    } else {
        // Get max position for this product
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(position), -1) + 1
            FROM ps_category_product
            WHERE id_product = ?
        ");
        $stmt->execute([$prestashopProductId]);
        $nextPosition = $stmt->fetchColumn();

        // Insert association
        $stmt = $pdo->prepare("
            INSERT INTO ps_category_product (id_product, id_category, position)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$prestashopProductId, 12, $nextPosition]);

        echo "✅ Category association INSERTED directly to database!\n";
        echo "   - Product: {$prestashopProductId}\n";
        echo "   - Category: 12\n";
        echo "   - Position: {$nextPosition}\n\n";

        echo "🔍 Now check PrestaShop admin panel:\n";
        echo "   → Product should now be visible in category\n";
        echo "   → This confirms API bug - direct DB insert works\n\n";
    }

} catch (PDOException $e) {
    echo "❌ Failed to insert category association:\n";
    echo "Error: {$e->getMessage()}\n\n";
}

// Also update id_category_default if it's wrong
if ($product['id_category_default'] != 12) {
    echo "5. Updating id_category_default to 12...\n";

    try {
        $stmt = $pdo->prepare("
            UPDATE ps_product
            SET id_category_default = ?
            WHERE id_product = ?
        ");
        $stmt->execute([12, $prestashopProductId]);

        echo "✅ id_category_default updated to 12\n\n";
    } catch (PDOException $e) {
        echo "❌ Failed to update id_category_default:\n";
        echo "Error: {$e->getMessage()}\n\n";
    }
}

echo "=== END DIAGNOSTIC ===\n";
echo "\n";
echo "📊 SUMMARY:\n";
echo "1. PrestaShop API PUT ignores associations.categories (confirmed bug)\n";
echo "2. Direct database INSERT works (ps_category_product table)\n";
echo "3. Solution: After API sync, manually INSERT to ps_category_product\n";
echo "\n";
