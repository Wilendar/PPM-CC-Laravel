<?php

/**
 * FULL DATABASE COMPARISON
 *
 * Find ALL tables containing product 9755 (good product)
 * Compare with product 9762 (test product)
 * Show what's missing
 */

echo "=== FULL DATABASE COMPARISON ===\n\n";
echo "Comparing:\n";
echo "  REFERENCE (good): Product ID 9755\n";
echo "  TEST (invisible): Product ID 9762\n\n";

$dbHost = 'host379076.hostido.net.pl';
$dbName = 'host379076_devmpp';
$dbUser = 'host379076_devmpp';
$dbPassword = 'CxtsfyV4nWyGct5LTZrb';

$referenceId = 9755;
$testId = 9762;

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Step 1: Find all tables with id_product column
    echo "Step 1: Finding all tables with 'id_product' column...\n";
    $stmt = $pdo->query("
        SELECT DISTINCT TABLE_NAME
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = '{$dbName}'
        AND COLUMN_NAME = 'id_product'
        AND TABLE_NAME LIKE 'ps_%'
        ORDER BY TABLE_NAME
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "✓ Found " . count($tables) . " tables with id_product column\n\n";

    // Step 2: Check each table for both products
    echo "Step 2: Checking each table for both products...\n\n";

    $tablesWithReference = [];
    $tablesWithTest = [];
    $missingInTest = [];

    foreach ($tables as $table) {
        // Check reference product
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE id_product = ?");
        $stmt->execute([$referenceId]);
        $refCount = $stmt->fetchColumn();

        // Check test product
        $stmt->execute([$testId]);
        $testCount = $stmt->fetchColumn();

        if ($refCount > 0) {
            $tablesWithReference[$table] = $refCount;
        }

        if ($testCount > 0) {
            $tablesWithTest[$table] = $testCount;
        }

        // If reference has records but test doesn't = MISSING!
        if ($refCount > 0 && $testCount == 0) {
            $missingInTest[$table] = $refCount;
        }
    }

    echo "SUMMARY:\n";
    echo "  Reference product (9755) present in: " . count($tablesWithReference) . " tables\n";
    echo "  Test product (9762) present in: " . count($tablesWithTest) . " tables\n";
    echo "  Missing in test product: " . count($missingInTest) . " tables\n\n";

    // Step 3: Show tables where reference exists
    echo "Step 3: Tables containing reference product (9755):\n\n";
    foreach ($tablesWithReference as $table => $count) {
        $inTest = isset($tablesWithTest[$table]) ? '✓' : '❌ MISSING';
        $testCount = isset($tablesWithTest[$table]) ? $tablesWithTest[$table] : 0;
        echo "  {$table}:\n";
        echo "    Reference: {$count} record(s)\n";
        echo "    Test: {$testCount} record(s) {$inTest}\n";
        echo "\n";
    }

    // Step 4: CRITICAL - Show what's missing
    if (!empty($missingInTest)) {
        echo "\n=== CRITICAL: MISSING IN TEST PRODUCT ===\n\n";
        echo "These tables have records for reference product but NOT for test product:\n\n";

        foreach ($missingInTest as $table => $count) {
            echo "❌ {$table} ({$count} record(s) in reference)\n";

            // Show sample data from reference product
            $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE id_product = ? LIMIT 1");
            $stmt->execute([$referenceId]);
            $sample = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($sample) {
                echo "   Sample record structure:\n";
                $importantFields = [];
                foreach ($sample as $key => $value) {
                    if (is_numeric($key)) continue;

                    // Skip id_product (we know that)
                    if ($key == 'id_product') continue;

                    // Show important fields
                    if (strpos($key, 'id_') === 0 || in_array($key, ['active', 'quantity', 'price', 'position'])) {
                        $displayValue = ($value === null) ? 'NULL' : (($value === '') ? 'EMPTY' : $value);
                        echo "     - {$key}: {$displayValue}\n";
                        $importantFields[$key] = $value;
                    }
                }
                echo "\n";
            }
        }

        echo "\n=== RECOMMENDATION ===\n\n";
        echo "To make test product visible like reference product, you need to add records to:\n\n";

        $priority = [
            'ps_specific_price' => 'HIGH - Specific prices/promotions',
            'ps_product_tag' => 'MEDIUM - Product tags',
            'ps_feature_product' => 'MEDIUM - Product features',
            'ps_product_download' => 'LOW - Virtual product downloads',
            'ps_customization_field' => 'LOW - Customization options',
            'ps_accessory' => 'LOW - Product accessories',
            'ps_pack' => 'LOW - Product packs',
        ];

        foreach ($missingInTest as $table => $count) {
            $priority_level = isset($priority[$table]) ? $priority[$table] : 'UNKNOWN';
            echo "  - {$table}: {$priority_level}\n";
        }

    } else {
        echo "\n✅ Test product has ALL the same table records as reference product!\n";
        echo "The visibility issue must be something else.\n";
    }

    // Step 5: Show exact record counts comparison
    echo "\n\n=== DETAILED RECORD COUNT COMPARISON ===\n\n";
    echo "Table | Reference | Test | Status\n";
    echo str_repeat("-", 70) . "\n";

    foreach ($tablesWithReference as $table => $refCount) {
        $testCount = isset($tablesWithTest[$table]) ? $tablesWithTest[$table] : 0;
        $status = ($refCount == $testCount) ? '✓ SAME' : '❌ DIFFERENT';

        printf("%-40s | %9d | %4d | %s\n", $table, $refCount, $testCount, $status);
    }

} catch (Exception $e) {
    echo "ERROR: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
}
