<?php

/**
 * CHECK: Why id_manufacturer is 0 again?
 */

echo "=== MANUFACTURER CHECK ===\n\n";

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

    // Check current manufacturer
    $stmt = $pdo->prepare("
        SELECT
            p.id_product,
            p.reference,
            p.id_manufacturer,
            m.name as manufacturer_name
        FROM ps_product p
        LEFT JOIN ps_manufacturer m ON p.id_manufacturer = m.id_manufacturer
        WHERE p.id_product = 9762
    ");
    $stmt->execute();
    $product = $stmt->fetch();

    echo "Product 9762 current state:\n";
    echo "  id_manufacturer: " . ($product['id_manufacturer'] ?: 'NULL/0') . "\n";
    echo "  manufacturer_name: " . ($product['manufacturer_name'] ?: 'NONE') . "\n\n";

    if (!$product['id_manufacturer'] || $product['id_manufacturer'] == 0) {
        echo "⚠️ Manufacturer is NULL or 0!\n";
        echo "Fixing now...\n\n";

        // Get a valid manufacturer (YCF = ID 17, same as product 9755)
        $stmt = $pdo->prepare("
            UPDATE ps_product
            SET id_manufacturer = 17
            WHERE id_product = 9762
        ");
        $stmt->execute();

        echo "✓ Updated id_manufacturer to 17 (YCF)\n\n";

        // Verify
        $stmt = $pdo->prepare("
            SELECT
                p.id_manufacturer,
                m.name
            FROM ps_product p
            LEFT JOIN ps_manufacturer m ON p.id_manufacturer = m.id_manufacturer
            WHERE p.id_product = 9762
        ");
        $stmt->execute();
        $verify = $stmt->fetch();

        echo "Verification:\n";
        echo "  id_manufacturer: {$verify['id_manufacturer']}\n";
        echo "  manufacturer_name: {$verify['name']}\n\n";
    } else {
        echo "✓ Manufacturer is set correctly!\n";
    }

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
}
