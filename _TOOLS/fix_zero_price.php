<?php

/**
 * FIX: Products with price = 0
 */

echo "=== FIX: ZERO PRICE PRODUCTS ===\n\n";

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

    // Find products with price = 0
    echo "Step 1: Finding products with price = 0...\n";
    $stmt = $pdo->query("
        SELECT
            p.id_product,
            p.reference,
            p.price,
            pl.name
        FROM ps_product p
        JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
        WHERE p.price = 0 OR p.price IS NULL
        AND p.reference LIKE 'TEST-%'
        ORDER BY p.id_product DESC
    ");
    $products = $stmt->fetchAll();

    if (empty($products)) {
        echo "✓ All TEST products have price set!\n";
        exit(0);
    }

    echo "❌ Found " . count($products) . " TEST products with price = 0:\n\n";
    foreach ($products as $prod) {
        echo "  - [ID: {$prod['id_product']}] {$prod['name']}\n";
        echo "    Reference: {$prod['reference']}\n";
        echo "    Current price: " . ($prod['price'] ?: '0.00') . "\n";
    }
    echo "\n";

    // Set minimal price (0.01)
    $minimalPrice = 0.01;
    echo "Will set minimal price: {$minimalPrice} EUR for these products.\n";
    echo "Proceeding...\n\n";

    // Update ps_product
    echo "Step 2: Updating ps_product table...\n";
    $stmt = $pdo->prepare("
        UPDATE ps_product
        SET price = ?
        WHERE (price = 0 OR price IS NULL)
        AND reference LIKE 'TEST-%'
    ");
    $stmt->execute([$minimalPrice]);
    $updated = $stmt->rowCount();
    echo "✓ Updated {$updated} records in ps_product\n\n";

    // Update ps_product_shop
    echo "Step 3: Updating ps_product_shop table...\n";
    $stmt = $pdo->prepare("
        UPDATE ps_product_shop
        SET price = ?
        WHERE (price = 0 OR price IS NULL)
        AND id_product IN (
            SELECT id_product
            FROM ps_product
            WHERE reference LIKE 'TEST-%'
        )
    ");
    $stmt->execute([$minimalPrice]);
    $updatedShop = $stmt->rowCount();
    echo "✓ Updated {$updatedShop} records in ps_product_shop\n\n";

    // Verify
    echo "Step 4: Verifying fix...\n";
    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM ps_product
        WHERE (price = 0 OR price IS NULL)
        AND reference LIKE 'TEST-%'
    ");
    $remaining = $stmt->fetchColumn();

    if ($remaining > 0) {
        echo "⚠️ Still {$remaining} TEST products with price = 0!\n";
    } else {
        echo "✅ All TEST products now have price > 0!\n\n";

        echo "=== SUCCESS ===\n\n";
        echo "Products with updated price:\n";
        foreach ($products as $prod) {
            echo "  - [ID: {$prod['id_product']}] {$prod['name']}\n";
            echo "    NEW price: {$minimalPrice} EUR\n";
        }
        echo "\n";

        echo "NEXT STEPS:\n";
        echo "1. Refresh PrestaShop admin panel (Ctrl+F5)\n";
        echo "2. Catalog → Products\n";
        echo "3. Search for: TEST-CREATE-1762351961\n";
        echo "4. Product should now be VISIBLE!\n\n";

        echo "NOTE: This is a temporary fix.\n";
        echo "You should update products with real prices.\n";
    }

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
