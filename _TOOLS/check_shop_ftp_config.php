<?php
/**
 * Check FTP config for shop 5
 */
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;

$shopId = 5;

echo "=== SHOP FTP CONFIG CHECK ===\n";
echo "Shop ID: {$shopId}\n\n";

$shop = PrestaShopShop::find($shopId);

if (!$shop) {
    die("Shop not found!\n");
}

echo "Shop Name: " . $shop->name . "\n";
echo "Shop URL: " . $shop->url . "\n\n";

echo "=== FTP CONFIG ===\n";
$ftpConfig = $shop->ftp_config ?? [];
if (empty($ftpConfig)) {
    echo "FTP CONFIG IS EMPTY!\n";
    echo "CSS sync mode will be 'pending' - external CSS cannot be uploaded!\n";
} else {
    foreach ($ftpConfig as $key => $value) {
        if ($key === 'password') {
            echo "  {$key}: ***hidden***\n";
        } else {
            echo "  {$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
        }
    }
}

echo "\n=== CSS MODE FOR THIS SHOP ===\n";
// Check what CSS mode would be determined
if (!empty($ftpConfig) && !empty($ftpConfig['host'])) {
    echo "Mode: external (FTP configured)\n";
} else {
    echo "Mode: pending (NO FTP - CSS sync blocked!)\n";
}
