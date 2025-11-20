<?php

/**
 * FIX: Add manufacturer to products created by PPM
 */

echo "=== FIX: MISSING MANUFACTURER ===\n\n";

$dbHost = 'host379076.hostido.net.pl';
$dbName = 'host379076_devmpp';
$dbUser = 'host379076_devmpp';
$dbPassword = 'CxtsfyV4nWyGct5LTZrb';

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Check available manufacturers
    echo "Step 1: Checking available manufacturers...\n";
    $stmt = $pdo->query("
        SELECT
            m.id_manufacturer,
            m.name,
            m.active
        FROM ps_manufacturer m
        ORDER BY m.name
    ");
    $manufacturers = $stmt->fetchAll();

    if (empty($manufacturers)) {
        echo "❌ No manufacturers found in database!\n";
        echo "You need to create at least one manufacturer first.\n";
        exit(1);
    }

    echo "✓ Found " . count($manufacturers) . " manufacturers:\n";
    foreach ($manufacturers as $mfr) {
        $activeStatus = $mfr['active'] ? 'ACTIVE' : 'INACTIVE';
        echo "  - [ID: {$mfr['id_manufacturer']}] {$mfr['name']} ({$activeStatus})\n";
    }
    echo "\n";

    // Find default or first manufacturer
    $defaultManufacturer = null;
    foreach ($manufacturers as $mfr) {
        if ($mfr['active']) {
            $defaultManufacturer = $mfr['id_manufacturer'];
            $defaultManufacturerName = $mfr['name'];
            break;
        }
    }

    if (!$defaultManufacturer) {
        echo "⚠️ No active manufacturer found, using first one anyway.\n";
        $defaultManufacturer = $manufacturers[0]['id_manufacturer'];
        $defaultManufacturerName = $manufacturers[0]['name'];
    }

    echo "Selected manufacturer: [ID: {$defaultManufacturer}] {$defaultManufacturerName}\n\n";

    // Find products without manufacturer
    echo "Step 2: Finding products without manufacturer...\n";
    $stmt = $pdo->query("
        SELECT
            p.id_product,
            p.reference,
            pl.name
        FROM ps_product p
        JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
        WHERE p.id_manufacturer IS NULL OR p.id_manufacturer = 0
        ORDER BY p.id_product DESC
    ");
    $productsToFix = $stmt->fetchAll();

    if (empty($productsToFix)) {
        echo "✓ All products have manufacturer assigned!\n";
        exit(0);
    }

    echo "❌ Found " . count($productsToFix) . " products without manufacturer:\n";
    foreach ($productsToFix as $prod) {
        echo "  - [ID: {$prod['id_product']}] {$prod['name']} (Ref: {$prod['reference']})\n";
    }
    echo "\n";

    // Ask for confirmation (auto-confirm for script)
    echo "Will assign manufacturer '{$defaultManufacturerName}' (ID: {$defaultManufacturer}) to these products.\n";
    echo "Proceeding...\n\n";

    // Fix ps_product
    echo "Step 3: Updating ps_product table...\n";
    $stmt = $pdo->prepare("
        UPDATE ps_product
        SET id_manufacturer = ?
        WHERE id_manufacturer IS NULL OR id_manufacturer = 0
    ");
    $stmt->execute([$defaultManufacturer]);
    $updatedCount = $stmt->rowCount();
    echo "✓ Updated {$updatedCount} records in ps_product\n\n";

    // Fix ps_product_shop
    echo "Step 4: Updating ps_product_shop table...\n";
    $stmt = $pdo->prepare("
        UPDATE ps_product_shop
        SET id_manufacturer = ?
        WHERE id_manufacturer IS NULL OR id_manufacturer = 0
    ");
    $stmt->execute([$defaultManufacturer]);
    $updatedShopCount = $stmt->rowCount();
    echo "✓ Updated {$updatedShopCount} records in ps_product_shop\n\n";

    // Verify fix
    echo "Step 5: Verifying fix...\n";
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM ps_product
        WHERE id_manufacturer IS NULL OR id_manufacturer = 0
    ");
    $remainingCount = $stmt->fetchColumn();

    if ($remainingCount > 0) {
        echo "⚠️ Still {$remainingCount} products without manufacturer!\n";
    } else {
        echo "✅ All products now have manufacturer assigned!\n\n";

        echo "=== SUCCESS ===\n\n";
        echo "Fixed products should now be visible in admin panel!\n\n";
        echo "NEXT STEPS:\n";
        echo "1. Go to PrestaShop admin: https://dev.mpptrade.pl/admin/\n";
        echo "2. Catalog → Products\n";
        echo "3. Search for: TEST-CREATE-1762351961\n";
        echo "4. Product should now be visible in the list!\n\n";

        echo "Updated products:\n";
        foreach ($productsToFix as $prod) {
            echo "  - [ID: {$prod['id_product']}] {$prod['name']}\n";
        }
    }

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
