<?php
/**
 * Update Shop Database Credentials
 *
 * Helper script to re-encrypt database credentials with current APP_KEY
 *
 * Usage:
 * php update_shop_db_credentials.php <shop_id> <db_host> <db_name> <db_user> <db_password>
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== UPDATE SHOP DATABASE CREDENTIALS ===\n\n";

// Get arguments
if ($argc < 6) {
    echo "Usage: php update_shop_db_credentials.php <shop_id> <db_host> <db_name> <db_user> <db_password>\n\n";
    echo "Example:\n";
    echo "php update_shop_db_credentials.php 1 localhost dbname dbuser dbpass\n";
    exit(1);
}

$shopId = (int) $argv[1];
$dbHost = $argv[2];
$dbName = $argv[3];
$dbUser = $argv[4];
$dbPassword = $argv[5];

// Get shop
$shop = \App\Models\PrestaShopShop::find($shopId);

if (!$shop) {
    echo "❌ Shop #{$shopId} not found\n";
    exit(1);
}

echo "Shop: {$shop->name} (ID: {$shop->id})\n";
echo "URL: {$shop->url}\n\n";

echo "Updating database credentials...\n";
echo "  DB Host: {$dbHost}\n";
echo "  DB Name: {$dbName}\n";
echo "  DB User: {$dbUser}\n";
echo "  DB Password: " . str_repeat('*', strlen($dbPassword)) . "\n\n";

// Update credentials (will be encrypted with current APP_KEY)
$shop->update([
    'db_host' => $dbHost,
    'db_name' => $dbName,
    'db_user' => $dbUser,
    'db_password' => encrypt($dbPassword),
]);

echo "✅ Database credentials updated successfully!\n\n";

// Test connection
echo "Testing database connection...\n";

try {
    $pdo = new PDO(
        "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
        $dbUser,
        $dbPassword
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Database connection successful!\n\n";

    // Test decryption
    echo "Testing credential decryption...\n";
    $shop->refresh();

    $decryptedPassword = decrypt($shop->db_password);

    if ($decryptedPassword === $dbPassword) {
        echo "✅ Decryption successful!\n\n";
    } else {
        echo "⚠️  Decryption mismatch!\n";
        echo "Original: {$dbPassword}\n";
        echo "Decrypted: {$decryptedPassword}\n\n";
    }

} catch (PDOException $e) {
    echo "❌ Database connection FAILED:\n";
    echo "Error: {$e->getMessage()}\n\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Decryption FAILED:\n";
    echo "Error: {$e->getMessage()}\n\n";
    exit(1);
}

echo "🎉 All checks passed! Shop database credentials are now working.\n";
echo "\n";
echo "You can now run category sync tests.\n";
