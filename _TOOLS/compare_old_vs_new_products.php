<?php

/**
 * COMPARE: Old products (visible in admin) vs New products (invisible)
 */

echo "=== OLD vs NEW PRODUCTS COMPARISON ===\n\n";

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

    // Get all products from PITGANG category
    echo "Step 1: Getting all products from PITGANG category...\n";
    $stmt = $pdo->query("
        SELECT
            p.id_product,
            p.reference,
            pl.name,
            p.active,
            p.date_add
        FROM ps_category_product cp
        JOIN ps_product p ON cp.id_product = p.id_product
        JOIN ps_product_lang pl ON p.id_product = pl.id_product AND pl.id_lang = 1
        WHERE cp.id_category = 12
        ORDER BY p.date_add DESC
    ");
    $products = $stmt->fetchAll();

    echo "✓ Found " . count($products) . " products:\n\n";

    $oldProduct = null; // Created in PrestaShop (visible in admin)
    $newProduct = null; // Created via PPM (invisible in admin)

    foreach ($products as $prod) {
        echo "  - [ID: {$prod['id_product']}] {$prod['name']}\n";
        echo "    Reference: {$prod['reference']}\n";
        echo "    Date: {$prod['date_add']}\n";
        echo "\n";

        // Identify old vs new
        if (strpos($prod['reference'], 'TEST-') !== false) {
            if (!$newProduct) $newProduct = $prod['id_product'];
        } else {
            if (!$oldProduct) $oldProduct = $prod['id_product'];
        }
    }

    if (!$oldProduct || !$newProduct) {
        echo "⚠️ Cannot identify old and new products for comparison\n";
        echo "Old product ID: " . ($oldProduct ?: 'NOT FOUND') . "\n";
        echo "New product ID: " . ($newProduct ?: 'NOT FOUND') . "\n";
        exit(1);
    }

    echo "Selected for comparison:\n";
    echo "  - OLD (visible): Product ID {$oldProduct}\n";
    echo "  - NEW (invisible): Product ID {$newProduct}\n\n";

    // Compare ALL columns in ps_product
    echo "Step 2: Comparing ps_product columns...\n";
    $stmt = $pdo->prepare("
        SELECT *
        FROM ps_product
        WHERE id_product IN (?, ?)
        ORDER BY id_product
    ");
    $stmt->execute([$oldProduct, $newProduct]);
    $productData = $stmt->fetchAll();

    $old = $productData[0];
    $new = $productData[1];

    $differences = [];
    foreach ($old as $key => $oldValue) {
        if (is_numeric($key)) continue; // Skip numeric indices
        if (in_array($key, ['id_product', 'reference', 'date_add', 'date_upd'])) continue; // Skip obvious differences

        $newValue = $new[$key];
        if ($oldValue != $newValue) {
            $differences[] = [
                'column' => $key,
                'old' => $oldValue,
                'new' => $newValue
            ];
        }
    }

    if (empty($differences)) {
        echo "✓ No structural differences in ps_product\n\n";
    } else {
        echo "❌ Found " . count($differences) . " differences:\n\n";
        foreach ($differences as $diff) {
            echo "  Column: {$diff['column']}\n";
            echo "    OLD product: " . ($diff['old'] ?: 'NULL') . "\n";
            echo "    NEW product: " . ($diff['new'] ?: 'NULL') . "\n";
            echo "\n";
        }
    }

    // Compare ps_product_shop
    echo "Step 3: Comparing ps_product_shop...\n";
    $stmt = $pdo->prepare("
        SELECT *
        FROM ps_product_shop
        WHERE id_product IN (?, ?)
        ORDER BY id_product
    ");
    $stmt->execute([$oldProduct, $newProduct]);
    $shopData = $stmt->fetchAll();

    if (count($shopData) < 2) {
        echo "❌ One of the products is MISSING from ps_product_shop!\n";
        if (count($shopData) == 0) {
            echo "CRITICAL: Both products missing!\n";
        } else {
            $existing = $shopData[0]['id_product'];
            $missing = ($existing == $oldProduct) ? $newProduct : $oldProduct;
            echo "CRITICAL: Product {$missing} is MISSING from ps_product_shop!\n";
        }
    } else {
        $oldShop = $shopData[0];
        $newShop = $shopData[1];

        $shopDifferences = [];
        foreach ($oldShop as $key => $oldValue) {
            if (is_numeric($key)) continue;
            if (in_array($key, ['id_product', 'date_add', 'date_upd'])) continue;

            $newValue = $newShop[$key];
            if ($oldValue != $newValue) {
                $shopDifferences[] = [
                    'column' => $key,
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        if (empty($shopDifferences)) {
            echo "✓ No differences in ps_product_shop\n\n";
        } else {
            echo "❌ Found " . count($shopDifferences) . " differences:\n\n";
            foreach ($shopDifferences as $diff) {
                echo "  Column: {$diff['column']}\n";
                echo "    OLD product: " . ($diff['old'] ?: 'NULL') . "\n";
                echo "    NEW product: " . ($diff['new'] ?: 'NULL') . "\n";
                echo "\n";
            }
        }
    }

    // Check for missing related tables
    echo "Step 4: Checking related tables presence...\n";
    $tables = [
        'ps_product_lang' => 'Language data',
        'ps_product_shop' => 'Shop assignment',
        'ps_category_product' => 'Category assignment',
        'ps_stock_available' => 'Stock data',
        'ps_image' => 'Product images',
        'ps_product_attribute' => 'Combinations/attributes',
    ];

    foreach ($tables as $table => $description) {
        echo "\n{$description} ({$table}):\n";
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE id_product = ?");

        $stmt->execute([$oldProduct]);
        $oldCount = $stmt->fetchColumn();

        $stmt->execute([$newProduct]);
        $newCount = $stmt->fetchColumn();

        $status = ($oldCount == $newCount) ? '✓' : '⚠️';
        echo "  OLD product: {$oldCount} records\n";
        echo "  NEW product: {$newCount} records {$status}\n";

        if ($oldCount != $newCount) {
            echo "  → DIFFERENCE: " . ($oldCount - $newCount) . " records\n";
        }
    }

    echo "\n=== SUMMARY ===\n\n";
    if (!empty($differences)) {
        echo "CRITICAL DIFFERENCES in ps_product:\n";
        foreach ($differences as $diff) {
            if ($diff['new'] === null || $diff['new'] === '' || $diff['new'] === '0') {
                echo "  ❌ NEW product missing: {$diff['column']}\n";
            }
        }
    }

    if (!empty($shopDifferences)) {
        echo "\nCRITICAL DIFFERENCES in ps_product_shop:\n";
        foreach ($shopDifferences as $diff) {
            if ($diff['new'] === null || $diff['new'] === '' || $diff['new'] === '0') {
                echo "  ❌ NEW product missing: {$diff['column']}\n";
            }
        }
    }

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
