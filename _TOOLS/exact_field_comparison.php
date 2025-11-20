<?php

/**
 * EXACT FIELD COMPARISON - Find EXACT difference
 */

echo "=== EXACT FIELD-BY-FIELD COMPARISON ===\n\n";

$dbHost = 'host379076.hostido.net.pl';
$dbName = 'host379076_devmpp';
$dbUser = 'host379076_devmpp';
$dbPassword = 'CxtsfyV4nWyGct5LTZrb';

$visibleId = 9755; // Good product
$invisibleId = 9762; // Test product

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Comparing:\n";
    echo "  VISIBLE: Product {$visibleId}\n";
    echo "  INVISIBLE: Product {$invisibleId}\n\n";

    // Get ALL columns from ps_product with exact values
    echo "=== ps_product TABLE ===\n\n";
    $stmt = $pdo->prepare("SELECT * FROM ps_product WHERE id_product = ?");

    $stmt->execute([$visibleId]);
    $visible = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt->execute([$invisibleId]);
    $invisible = $stmt->fetch(PDO::FETCH_ASSOC);

    $criticalDifferences = [];

    foreach ($visible as $field => $vValue) {
        if (is_numeric($field)) continue;

        $iValue = $invisible[$field];

        // Skip obvious ones
        if (in_array($field, ['id_product', 'reference', 'date_add', 'date_upd', 'ean13'])) continue;

        // Check for differences
        if ($vValue != $iValue) {
            $vDisplay = $vValue;
            $iDisplay = $iValue;

            // Highlight NULLs and zeros
            if ($vValue === null) $vDisplay = 'NULL';
            if ($iValue === null) $iDisplay = 'NULL';
            if ($vValue === '') $vDisplay = "''";
            if ($iValue === '') $iDisplay = "''";

            echo "FIELD: {$field}\n";
            echo "  VISIBLE:   {$vDisplay}\n";
            echo "  INVISIBLE: {$iDisplay}\n";

            // Identify critical differences
            if ($vValue !== null && $vValue !== '' && $vValue !== '0') {
                if ($iValue === null || $iValue === '' || $iValue === '0') {
                    echo "  ⚠️ CRITICAL: Invisible has NULL/EMPTY/ZERO!\n";
                    $criticalDifferences[$field] = [
                        'visible' => $vValue,
                        'invisible' => $iValue
                    ];
                }
            }

            echo "\n";
        }
    }

    // Same for ps_product_shop
    echo "\n=== ps_product_shop TABLE ===\n\n";
    $stmt = $pdo->prepare("SELECT * FROM ps_product_shop WHERE id_product = ?");

    $stmt->execute([$visibleId]);
    $visibleShop = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt->execute([$invisibleId]);
    $invisibleShop = $stmt->fetch(PDO::FETCH_ASSOC);

    foreach ($visibleShop as $field => $vValue) {
        if (is_numeric($field)) continue;
        if (in_array($field, ['id_product', 'date_add', 'date_upd'])) continue;

        $iValue = $invisibleShop[$field];

        if ($vValue != $iValue) {
            $vDisplay = $vValue;
            $iDisplay = $iValue;

            if ($vValue === null) $vDisplay = 'NULL';
            if ($iValue === null) $iDisplay = 'NULL';
            if ($vValue === '') $vDisplay = "''";
            if ($iValue === '') $iDisplay = "''";

            echo "FIELD: {$field}\n";
            echo "  VISIBLE:   {$vDisplay}\n";
            echo "  INVISIBLE: {$iDisplay}\n";

            if ($vValue !== null && $vValue !== '' && $vValue !== '0') {
                if ($iValue === null || $iValue === '' || $iValue === '0') {
                    echo "  ⚠️ CRITICAL: Invisible has NULL/EMPTY/ZERO!\n";
                    $criticalDifferences["shop.{$field}"] = [
                        'visible' => $vValue,
                        'invisible' => $iValue
                    ];
                }
            }

            echo "\n";
        }
    }

    // Show SQL to copy exact values
    if (!empty($criticalDifferences)) {
        echo "\n=== CRITICAL FIELDS TO COPY ===\n\n";
        echo "These fields have values in VISIBLE but NULL/EMPTY/ZERO in INVISIBLE:\n\n";

        $productUpdates = [];
        $shopUpdates = [];

        foreach ($criticalDifferences as $field => $values) {
            echo "  - {$field}: {$values['visible']}\n";

            if (strpos($field, 'shop.') === 0) {
                $realField = str_replace('shop.', '', $field);
                $shopUpdates[] = "{$realField} = " . $pdo->quote($values['visible']);
            } else {
                $productUpdates[] = "{$field} = " . $pdo->quote($values['visible']);
            }
        }

        echo "\n=== COPY VALUES SQL ===\n\n";

        if (!empty($productUpdates)) {
            echo "UPDATE ps_product SET\n";
            echo "  " . implode(",\n  ", $productUpdates) . "\n";
            echo "WHERE id_product = {$invisibleId};\n\n";
        }

        if (!empty($shopUpdates)) {
            echo "UPDATE ps_product_shop SET\n";
            echo "  " . implode(",\n  ", $shopUpdates) . "\n";
            echo "WHERE id_product = {$invisibleId};\n\n";
        }
    }

    // Check if it's a hidden filter issue - check active, visibility, etc
    echo "\n=== VISIBILITY FLAGS CHECK ===\n\n";
    echo "ps_product:\n";
    echo "  active: V={$visible['active']} vs I={$invisible['active']}\n";
    echo "  visibility: V={$visible['visibility']} vs I={$invisible['visibility']}\n";
    echo "  available_for_order: V={$visible['available_for_order']} vs I={$invisible['available_for_order']}\n";
    echo "  show_price: V={$visible['show_price']} vs I={$invisible['show_price']}\n";
    echo "  indexed: V={$visible['indexed']} vs I={$invisible['indexed']}\n";
    echo "\n";

    echo "ps_product_shop:\n";
    echo "  active: V={$visibleShop['active']} vs I={$invisibleShop['active']}\n";
    echo "  visibility: V={$visibleShop['visibility']} vs I={$invisibleShop['visibility']}\n";
    echo "  available_for_order: V={$visibleShop['available_for_order']} vs I={$invisibleShop['available_for_order']}\n";

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
