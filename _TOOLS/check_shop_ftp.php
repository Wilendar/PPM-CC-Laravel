<?php
/**
 * Check shop FTP configuration
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$shop = App\Models\PrestaShopShop::find(5);

if (!$shop) {
    echo "Shop 5 not found\n";
    exit(1);
}

echo "=== Shop FTP Config ===\n\n";
echo "Shop ID: " . $shop->id . "\n";
echo "Shop Name: " . $shop->name . "\n\n";
echo "FTP Config:\n";
echo json_encode($shop->ftp_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
