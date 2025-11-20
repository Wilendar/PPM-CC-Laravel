$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$LaravelRoot = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== Shop Creation Analysis ===" -ForegroundColor Cyan

# Create diagnostic script
$diagnosticScript = @'
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\PrestaShopShop;
use App\Models\ShopMapping;
use Illuminate\Support\Facades\DB;

$shop = PrestaShopShop::find(1);

if (!$shop) {
    echo "ERROR: Shop not found\n";
    exit(1);
}

echo "=== SHOP DETAILS ===\n";
echo "ID: {$shop->id}\n";
echo "Name: {$shop->name}\n";
echo "URL: {$shop->url}\n";
echo "Version: {$shop->prestashop_version}\n";
echo "Created: {$shop->created_at}\n";
echo "Updated: {$shop->updated_at}\n\n";

echo "=== SHOP MAPPINGS ===\n";
$mappings = ShopMapping::where('shop_id', $shop->id)->get();
echo "Total mappings: {$mappings->count()}\n\n";

if ($mappings->isEmpty()) {
    echo "NO MAPPINGS FOUND!\n";
    echo "This suggests shop was created via:\n";
    echo "- Direct database insert/seeder\n";
    echo "- Migration without wizard\n";
    echo "- Wizard skipped Step 4 (price mappings)\n\n";
} else {
    foreach ($mappings as $m) {
        echo "Type: {$m->mapping_type}, PPM: {$m->ppm_value}, PS: {$m->prestashop_id}, Active: " . ($m->is_active ? 'YES' : 'NO') . "\n";
    }
    echo "\n";
}

echo "=== RECOMMENDATION ===\n";
echo "Go to: Admin -> Shops -> Edit 'B2B Test DEV' -> Step 4: Price Group Mapping\n";
echo "OR manually create mappings via ShopMapping model\n";
'@

echo $diagnosticScript | Out-File -FilePath "$env:TEMP\check_shop.php" -Encoding UTF8 -NoNewline
pscp -i $HostidoKey -P $HostidoPort "$env:TEMP\check_shop.php" "${HostidoHost}:${LaravelRoot}/check_shop.php" 2>&1 | Out-Null

plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $LaravelRoot && php check_shop.php"

plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $LaravelRoot && rm check_shop.php"
Remove-Item "$env:TEMP\check_shop.php" -Force

Write-Host ""
Write-Host "Done." -ForegroundColor Green
