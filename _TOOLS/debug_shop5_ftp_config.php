<?php
// Debug ftp_config for shop 5

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;

$shop = PrestaShopShop::find(5);

echo "=== SHOP 5 FTP_CONFIG ===\n\n";

$ftpConfig = $shop->ftp_config ?? [];

if (empty($ftpConfig)) {
    echo "ftp_config is EMPTY or NULL!\n";
} else {
    echo "ftp_config keys: " . implode(', ', array_keys($ftpConfig)) . "\n\n";
    foreach ($ftpConfig as $key => $value) {
        if ($key === 'password') {
            echo "$key: [HIDDEN]\n";
        } else {
            echo "$key: " . ($value ?? 'NULL') . "\n";
        }
    }
}

echo "\n=== isCssSyncEnabled check ===\n";
$hasHost = !empty($ftpConfig['host']);
echo "ftp_config['host'] set: " . ($hasHost ? 'YES' : 'NO') . "\n";
