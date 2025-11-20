$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== CHECKING PRICE MAPPING FOR PRODUCT 11020 ===" -ForegroundColor Cyan

$phpScript = @'
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

$product = Product::find(11020);
$price = $product->prices()->first();

if (!$price) {
    echo "No prices found\n";
    exit(1);
}

echo "=== PRICE RECORD ===\n";
echo "Price ID: {$price->id}\n";
echo "Created: {$price->created_at}\n";
echo "Updated: {$price->updated_at}\n";
echo "\nPrestaShop Mapping:\n";
echo json_encode($price->prestashop_mapping, JSON_PRETTY_PRINT);
echo "\n\n";

// Check shop data
$shopData = $product->shopData()->first();
if ($shopData) {
    echo "=== SHOP DATA ===\n";
    echo "Last Pulled At: {$shopData->last_pulled_at}\n";
    echo "PrestaShop Product ID: {$shopData->prestashop_product_id}\n";
}
'@

$phpScript | Out-File -FilePath "_TEMP\check_mapping.php" -Encoding UTF8 -NoNewline

pscp -i $HostidoKey -P 64321 "_TEMP\check_mapping.php" "${RemoteHost}:${RemoteBase}/check_mapping.php" 2>&1 | Out-Null
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php check_mapping.php"
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemoteBase && rm -f check_mapping.php" 2>&1 | Out-Null

Write-Host "`n=== COMPLETE ===" -ForegroundColor Green
