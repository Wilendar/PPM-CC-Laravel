<?php

/**
 * TEST: Assign Product to Category in PrestaShop via Direct SQL
 *
 * Purpose: Test category-product association using ps_category_product table
 *
 * Strategy:
 * 1. Find existing active product in PrestaShop
 * 2. Check product's current categories
 * 3. Assign product to TEST PPM Category (ID 2352)
 * 4. Update ps_product.id_category_default if needed
 * 5. Verify assignment in database
 *
 * Database: B2B Test DEV (dev.mpptrade.pl)
 *
 * @version 1.0
 * @since 2025-11-06
 */

echo "=== PRESTASHOP PRODUCT-CATEGORY ASSIGNMENT TEST ===\n\n";

// Database credentials (B2B Test DEV)
$dbHost = 'host379076.hostido.net.pl';
$dbName = 'host379076_devmpp';
$dbUser = 'host379076_devmpp';
$dbPassword = 'CxtsfyV4nWyGct5LTZrb';

// Test data
$testCategoryId = 2352; // TEST PPM Category created earlier
$idShop = 1;
$idLang = 1;

try {
    echo "Step 1: Connecting to PrestaShop database...\n";
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✓ Connected successfully!\n\n";

    // Step 2: Find existing active product
    echo "Step 2: Finding active product in PrestaShop...\n";
    $stmt = $pdo->prepare("
        SELECT
            p.id_product,
            p.reference,
            p.id_category_default,
            p.active,
            pl.name
        FROM ps_product p
        JOIN ps_product_lang pl ON p.id_product = pl.id_product
        WHERE p.active = 1 AND pl.id_lang = ?
        ORDER BY p.id_product DESC
        LIMIT 1
    ");
    $stmt->execute([$idLang]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception("No active products found in PrestaShop!");
    }

    $productId = $product['id_product'];

    echo "✓ Product found:\n";
    echo "  - ID: {$productId}\n";
    echo "  - Reference: {$product['reference']}\n";
    echo "  - Name: {$product['name']}\n";
    echo "  - Default Category: {$product['id_category_default']}\n";
    echo "  - Active: {$product['active']}\n\n";

    // Step 3: Check product's current categories
    echo "Step 3: Checking product's current categories...\n";
    $stmt = $pdo->prepare("
        SELECT
            cp.id_category,
            cp.position,
            cl.name
        FROM ps_category_product cp
        JOIN ps_category_lang cl ON cp.id_category = cl.id_category
        WHERE cp.id_product = ? AND cl.id_lang = ?
        ORDER BY cp.position
    ");
    $stmt->execute([$productId, $idLang]);
    $currentCategories = $stmt->fetchAll();

    echo "✓ Product currently assigned to " . count($currentCategories) . " categories:\n";
    foreach ($currentCategories as $cat) {
        echo "  - [{$cat['id_category']}] {$cat['name']} (position: {$cat['position']})\n";
    }
    echo "\n";

    // Step 4: Check if already assigned to test category
    $stmt = $pdo->prepare("
        SELECT id_category
        FROM ps_category_product
        WHERE id_product = ? AND id_category = ?
    ");
    $stmt->execute([$productId, $testCategoryId]);
    $alreadyAssigned = $stmt->fetch();

    if ($alreadyAssigned) {
        echo "ℹ Product is already assigned to TEST PPM Category (ID: {$testCategoryId})\n";
        echo "Skipping assignment step.\n\n";
    } else {
        echo "Step 4: Assigning product to TEST PPM Category (ID: {$testCategoryId})...\n";

        // Get next position for this product
        $stmt = $pdo->prepare("
            SELECT COALESCE(MAX(position), -1) + 1
            FROM ps_category_product
            WHERE id_product = ?
        ");
        $stmt->execute([$productId]);
        $nextPosition = $stmt->fetchColumn();

        // Insert association
        $stmt = $pdo->prepare("
            INSERT INTO ps_category_product (id_product, id_category, position)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$productId, $testCategoryId, $nextPosition]);

        echo "✓ Product assigned to category!\n";
        echo "  - Position: {$nextPosition}\n\n";
    }

    // Step 5: Update default category if needed
    echo "Step 5: Checking if default category should be updated...\n";
    if ($product['id_category_default'] != $testCategoryId) {
        // Only update if product has no default or if user confirms
        if ($product['id_category_default'] == 0 || empty($product['id_category_default'])) {
            echo "Product has no default category. Setting TEST PPM Category as default...\n";
            $stmt = $pdo->prepare("
                UPDATE ps_product
                SET id_category_default = ?
                WHERE id_product = ?
            ");
            $stmt->execute([$testCategoryId, $productId]);
            echo "✓ Default category updated!\n\n";
        } else {
            echo "ℹ Product already has default category (ID: {$product['id_category_default']})\n";
            echo "Keeping existing default category.\n\n";
        }
    } else {
        echo "✓ Product already has TEST PPM Category as default.\n\n";
    }

    // Step 6: Verify assignment
    echo "Step 6: Verifying category assignment...\n";
    $stmt = $pdo->prepare("
        SELECT
            cp.id_category,
            cp.position,
            cl.name,
            c.active
        FROM ps_category_product cp
        JOIN ps_category c ON cp.id_category = c.id_category
        JOIN ps_category_lang cl ON c.id_category = cl.id_category
        WHERE cp.id_product = ? AND cl.id_lang = ?
        ORDER BY cp.position
    ");
    $stmt->execute([$productId, $idLang]);
    $finalCategories = $stmt->fetchAll();

    echo "✓ Product now assigned to " . count($finalCategories) . " categories:\n";
    foreach ($finalCategories as $cat) {
        $isTest = ($cat['id_category'] == $testCategoryId) ? ' ← TEST CATEGORY' : '';
        echo "  - [{$cat['id_category']}] {$cat['name']} (position: {$cat['position']}, active: {$cat['active']}){$isTest}\n";
    }
    echo "\n";

    // Step 7: Get product's current default category
    $stmt = $pdo->prepare("
        SELECT id_category_default
        FROM ps_product
        WHERE id_product = ?
    ");
    $stmt->execute([$productId]);
    $defaultCategoryId = $stmt->fetchColumn();

    echo "✓ Product's default category: {$defaultCategoryId}\n\n";

    echo "=== SUCCESS ===\n";
    echo "Product successfully associated with TEST PPM Category!\n\n";

    echo "Summary:\n";
    echo "  - Product ID: {$productId}\n";
    echo "  - Product Name: {$product['name']}\n";
    echo "  - Test Category ID: {$testCategoryId}\n";
    echo "  - Total Categories: " . count($finalCategories) . "\n";
    echo "  - Default Category: {$defaultCategoryId}\n\n";

    echo "Next Steps:\n";
    echo "1. Open PrestaShop admin panel\n";
    echo "2. Go to Katalog → Produkty\n";
    echo "3. Find product: {$product['name']} (ID: {$productId})\n";
    echo "4. Check if 'TEST PPM Category' is listed in product's categories\n";
    echo "5. Go to Katalog → Kategorie → TEST PPM Category\n";
    echo "6. Verify product appears in this category's product list\n\n";

} catch (PDOException $e) {
    echo "❌ DATABASE ERROR:\n";
    echo "Message: {$e->getMessage()}\n";
    echo "Code: {$e->getCode()}\n\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERROR:\n";
    echo "Message: {$e->getMessage()}\n\n";
    exit(1);
}
