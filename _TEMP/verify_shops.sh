#!/bin/bash
cd domains/ppm.mpptrade.pl/public_html
php artisan tinker << 'EOF'
use App\Models\PrestaShopShop;
echo "=== SHOP #1 ===" . PHP_EOL;
$shop1 = PrestaShopShop::find(1);
if ($shop1) {
    echo "Name: " . $shop1->name . PHP_EOL;
    echo "URL: " . $shop1->ps_api_url . PHP_EOL;
    echo "API Key: " . substr($shop1->ps_api_key, 0, 15) . "..." . PHP_EOL;
    echo "Webservice: " . ($shop1->ps_webservice_enabled ? 'enabled' : 'disabled') . PHP_EOL;
    echo "Version: " . $shop1->prestashop_version . PHP_EOL;
} else {
    echo "NOT FOUND" . PHP_EOL;
}
echo PHP_EOL;
echo "=== SHOP #5 ===" . PHP_EOL;
$shop5 = PrestaShopShop::find(5);
if ($shop5) {
    echo "Name: " . $shop5->name . PHP_EOL;
    echo "URL: " . $shop5->ps_api_url . PHP_EOL;
    echo "API Key: " . substr($shop5->ps_api_key, 0, 15) . "..." . PHP_EOL;
    echo "Webservice: " . ($shop5->ps_webservice_enabled ? 'enabled' : 'disabled') . PHP_EOL;
    echo "Version: " . $shop5->prestashop_version . PHP_EOL;
} else {
    echo "NOT FOUND" . PHP_EOL;
}
exit
EOF
