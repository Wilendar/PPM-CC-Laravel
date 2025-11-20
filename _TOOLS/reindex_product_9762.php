<?php

/**
 * FORCE REINDEX: Product 9762 in PrestaShop
 */

echo "=== FORCE REINDEX PRODUCT 9762 ===\n\n";

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

    echo "Step 1: Update indexed flag...\n";
    $stmt = $pdo->prepare("UPDATE ps_product SET indexed = 0 WHERE id_product = 9762");
    $stmt->execute();
    echo "✓ Set indexed = 0 (force reindex)\n\n";

    $stmt = $pdo->prepare("UPDATE ps_product SET indexed = 1 WHERE id_product = 9762");
    $stmt->execute();
    echo "✓ Set indexed = 1\n\n";

    echo "Step 2: Update product_shop indexed flag...\n";
    $stmt = $pdo->prepare("UPDATE ps_product_shop SET indexed = 0 WHERE id_product = 9762");
    $stmt->execute();
    $stmt = $pdo->prepare("UPDATE ps_product_shop SET indexed = 1 WHERE id_product = 9762");
    $stmt->execute();
    echo "✓ Updated ps_product_shop\n\n";

    echo "Step 3: Touch date_upd to trigger updates...\n";
    $stmt = $pdo->prepare("UPDATE ps_product SET date_upd = NOW() WHERE id_product = 9762");
    $stmt->execute();
    echo "✓ Updated date_upd\n\n";

    echo "=== REINDEX COMPLETE ===\n\n";
    echo "Now try to:\n";
    echo "1. Go to PrestaShop admin panel\n";
    echo "2. Advanced Parameters → Performance → Clear cache\n";
    echo "3. Catalog → Products → Search for SKU: TEST-CREATE-1762351961\n";
    echo "4. Or search by ID: 9762\n";

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
}
