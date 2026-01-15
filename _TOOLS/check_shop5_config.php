<?php
// Check shop 5 config

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\PrestaShopShop;

$shop = PrestaShopShop::find(5);
if (!$shop) {
    echo "Shop 5 not found\n";
    exit(1);
}

echo "Shop: {$shop->name}\n";
echo "FTP Host: {$shop->ftp_host}\n";
echo "FTP Path: {$shop->ftp_path}\n";
echo "CSS Path: {$shop->css_path}\n";
echo "Theme: {$shop->theme_name}\n";
echo "Has FTP: " . ($shop->ftp_host ? 'Yes' : 'No') . "\n";
