<?php

/**
 * TEST: Create Category in PrestaShop via Direct SQL
 *
 * Purpose: Test workaround dla PrestaShop 8 API bug (API ignores categories)
 *
 * Strategy:
 * 1. Connect to PrestaShop database directly
 * 2. Check if category exists by name
 * 3. If not - create category with proper Nested Set Model values
 * 4. Insert to 3 tables: ps_category, ps_category_lang, ps_category_shop
 * 5. Return PrestaShop category ID
 *
 * Database: B2B Test DEV (dev.mpptrade.pl)
 * - Host: host379076.hostido.net.pl
 * - Database: host379076_devmpp
 * - User: host379076_devmpp
 * - Password: CxtsfyV4nWyGct5LTZrb
 *
 * @version 1.0
 * @since 2025-11-06
 */

echo "=== PRESTASHOP CATEGORY CREATION TEST ===\n\n";

// Database credentials (B2B Test DEV)
$dbHost = 'host379076.hostido.net.pl';
$dbName = 'host379076_devmpp';
$dbUser = 'host379076_devmpp';
$dbPassword = 'CxtsfyV4nWyGct5LTZrb';

// Test category data
$categoryName = 'TEST PPM Category';
$categoryNameSlug = 'test-ppm-category';
$categoryDescription = 'Test category created by PPM via direct SQL';
$parentCategoryId = 2; // Home category (ID 2 is standard PrestaShop home category)
$idShop = 1; // Default shop
$idLang = 1; // English

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

    // Step 2: Check if category already exists
    echo "Step 2: Checking if category '{$categoryName}' already exists...\n";
    $stmt = $pdo->prepare("
        SELECT c.id_category, cl.name
        FROM ps_category c
        JOIN ps_category_lang cl ON c.id_category = cl.id_category
        WHERE cl.name = ? AND cl.id_lang = ?
        LIMIT 1
    ");
    $stmt->execute([$categoryName, $idLang]);
    $existingCategory = $stmt->fetch();

    if ($existingCategory) {
        echo "✓ Category already exists!\n";
        echo "  - PrestaShop ID: {$existingCategory['id_category']}\n";
        echo "  - Name: {$existingCategory['name']}\n\n";
        echo "Result: Category ID = {$existingCategory['id_category']}\n";
        exit(0);
    } else {
        echo "✗ Category does not exist. Creating new category...\n\n";
    }

    // Step 3: Get parent category info (for Nested Set Model)
    echo "Step 3: Getting parent category info (ID: {$parentCategoryId})...\n";
    $stmt = $pdo->prepare("
        SELECT id_category, level_depth, nleft, nright
        FROM ps_category
        WHERE id_category = ?
    ");
    $stmt->execute([$parentCategoryId]);
    $parentCategory = $stmt->fetch();

    if (!$parentCategory) {
        throw new Exception("Parent category ID {$parentCategoryId} not found!");
    }

    echo "✓ Parent category found:\n";
    echo "  - ID: {$parentCategory['id_category']}\n";
    echo "  - Level: {$parentCategory['level_depth']}\n";
    echo "  - NLeft: {$parentCategory['nleft']}\n";
    echo "  - NRight: {$parentCategory['nright']}\n\n";

    // Step 4: Calculate Nested Set Model values for new category
    echo "Step 4: Calculating Nested Set Model values...\n";
    $newLevelDepth = $parentCategory['level_depth'] + 1;
    $newNLeft = $parentCategory['nright'];
    $newNRight = $newNLeft + 1;

    echo "✓ New category will have:\n";
    echo "  - Level Depth: {$newLevelDepth}\n";
    echo "  - NLeft: {$newNLeft}\n";
    echo "  - NRight: {$newNRight}\n\n";

    // Step 5: Update existing categories' Nested Set values
    echo "Step 5: Updating Nested Set values for existing categories...\n";
    $pdo->beginTransaction();

    // Update nleft for all categories where nleft >= newNLeft
    $stmt = $pdo->prepare("UPDATE ps_category SET nleft = nleft + 2 WHERE nleft >= ?");
    $stmt->execute([$newNLeft]);
    $updatedLeft = $stmt->rowCount();

    // Update nright for all categories where nright >= newNLeft
    $stmt = $pdo->prepare("UPDATE ps_category SET nright = nright + 2 WHERE nright >= ?");
    $stmt->execute([$newNLeft]);
    $updatedRight = $stmt->rowCount();

    echo "✓ Updated {$updatedLeft} categories (nleft)\n";
    echo "✓ Updated {$updatedRight} categories (nright)\n\n";

    // Step 6: Insert into ps_category
    echo "Step 6: Inserting into ps_category...\n";
    $stmt = $pdo->prepare("
        INSERT INTO ps_category (
            id_parent, level_depth, nleft, nright, active,
            date_add, date_upd, position, is_root_category
        ) VALUES (
            ?, ?, ?, ?, 1,
            NOW(), NOW(), 0, 0
        )
    ");
    $stmt->execute([
        $parentCategoryId,
        $newLevelDepth,
        $newNLeft,
        $newNRight,
    ]);
    $newCategoryId = $pdo->lastInsertId();
    echo "✓ Category created! New ID: {$newCategoryId}\n\n";

    // Step 7: Insert into ps_category_lang
    echo "Step 7: Inserting into ps_category_lang...\n";
    $stmt = $pdo->prepare("
        INSERT INTO ps_category_lang (
            id_category, id_shop, id_lang, name, description,
            link_rewrite, meta_title, meta_description, meta_keywords
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ''
        )
    ");
    $stmt->execute([
        $newCategoryId,
        $idShop,
        $idLang,
        $categoryName,
        $categoryDescription,
        $categoryNameSlug,
        $categoryName, // meta_title
        $categoryDescription, // meta_description
    ]);
    echo "✓ Language data inserted!\n\n";

    // Step 8: Insert into ps_category_shop
    echo "Step 8: Inserting into ps_category_shop...\n";
    $stmt = $pdo->prepare("
        INSERT INTO ps_category_shop (
            id_category, id_shop, position
        ) VALUES (
            ?, ?, 0
        )
    ");
    $stmt->execute([$newCategoryId, $idShop]);
    echo "✓ Shop association created!\n\n";

    // Commit transaction
    $pdo->commit();
    echo "✅ TRANSACTION COMMITTED!\n\n";

    // Step 9: Verify creation
    echo "Step 9: Verifying category creation...\n";
    $stmt = $pdo->prepare("
        SELECT
            c.id_category,
            c.id_parent,
            c.level_depth,
            c.nleft,
            c.nright,
            c.active,
            cl.name,
            cl.link_rewrite,
            cs.id_shop
        FROM ps_category c
        JOIN ps_category_lang cl ON c.id_category = cl.id_category
        JOIN ps_category_shop cs ON c.id_category = cs.id_category
        WHERE c.id_category = ? AND cl.id_lang = ?
    ");
    $stmt->execute([$newCategoryId, $idLang]);
    $verifyCategory = $stmt->fetch();

    if (!$verifyCategory) {
        throw new Exception("Verification failed! Category not found after creation!");
    }

    echo "✓ Category verified in database:\n";
    echo "  - ID: {$verifyCategory['id_category']}\n";
    echo "  - Name: {$verifyCategory['name']}\n";
    echo "  - Parent ID: {$verifyCategory['id_parent']}\n";
    echo "  - Level: {$verifyCategory['level_depth']}\n";
    echo "  - NLeft: {$verifyCategory['nleft']}\n";
    echo "  - NRight: {$verifyCategory['nright']}\n";
    echo "  - Active: {$verifyCategory['active']}\n";
    echo "  - URL Slug: {$verifyCategory['link_rewrite']}\n";
    echo "  - Shop ID: {$verifyCategory['id_shop']}\n\n";

    echo "=== SUCCESS ===\n";
    echo "Category created successfully!\n";
    echo "PrestaShop Category ID: {$newCategoryId}\n\n";
    echo "Next Steps:\n";
    echo "1. Verify category is visible in PrestaShop admin panel\n";
    echo "2. Use this category ID for product association\n";
    echo "3. Test product sync with this category\n\n";

} catch (PDOException $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
        echo "✗ TRANSACTION ROLLED BACK!\n\n";
    }
    echo "❌ DATABASE ERROR:\n";
    echo "Message: {$e->getMessage()}\n";
    echo "Code: {$e->getCode()}\n\n";
    exit(1);
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
        echo "✗ TRANSACTION ROLLED BACK!\n\n";
    }
    echo "❌ ERROR:\n";
    echo "Message: {$e->getMessage()}\n\n";
    exit(1);
}
