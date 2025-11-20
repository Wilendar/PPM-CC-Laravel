$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== CHECKING PRODUCT 11020 ON PRODUCTION ===" -ForegroundColor Cyan

# Create temp script on production
$phpScript = @'
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Product;

$product = Product::with(['prices.priceGroup'])->find(11020);

if (!$product) {
    echo "Product 11020 not found\n";
    exit(1);
}

echo "SKU: {$product->sku}\n";
echo "Tax Rate: {$product->tax_rate}%\n";
echo "\n";

foreach ($product->prices as $price) {
    echo "Group: {$price->priceGroup->code} | Net: {$price->price_net} | Gross: {$price->price_gross}\n";
}
'@

# Save locally
$phpScript | Out-File -FilePath "_TEMP\check_prod_11020.php" -Encoding UTF8 -NoNewline

# Upload to production
Write-Host "`n[1/3] Uploading check script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "_TEMP\check_prod_11020.php" "${RemoteHost}:${RemoteBase}/check_prod_11020.php"

# Execute on production
Write-Host "`n[2/3] Executing on production..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php check_prod_11020.php"

# Cleanup
Write-Host "`n[3/3] Cleaning up..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemoteBase && rm -f check_prod_11020.php"

Write-Host "`n=== CHECK COMPLETE ===" -ForegroundColor Green
