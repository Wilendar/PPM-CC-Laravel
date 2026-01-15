<?php

/**
 * Check shop CSS configuration
 */

require_once __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrestaShopShop;

$shop = PrestaShopShop::find(5);

if (!$shop) {
    echo "Shop not found\n";
    exit(1);
}

echo "Shop ID: {$shop->id}\n";
echo "Name: {$shop->name}\n";
echo "Custom CSS URL: " . ($shop->custom_css_url ?? 'NOT SET') . "\n";
echo "Cached CSS: " . ($shop->cached_custom_css ? strlen($shop->cached_custom_css) . ' bytes' : 'NOT CACHED') . "\n";
echo "FTP Config: " . ($shop->ftp_config ? json_encode(array_keys($shop->ftp_config)) : 'NOT SET') . "\n";
