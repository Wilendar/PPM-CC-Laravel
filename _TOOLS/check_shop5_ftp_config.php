<?php
// Check shop 5 FTP config (correct field)

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
echo "Custom CSS URL: {$shop->custom_css_url}\n";
echo "FTP Config:\n";
print_r($shop->ftp_config);

echo "\n--- All shop attributes ---\n";
$attrs = $shop->getAttributes();
foreach ($attrs as $key => $value) {
    if (str_contains($key, 'ftp') || str_contains($key, 'css') || str_contains($key, 'theme')) {
        echo "{$key}: " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }
}
