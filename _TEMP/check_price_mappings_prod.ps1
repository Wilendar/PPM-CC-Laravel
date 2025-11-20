$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$LaravelRoot = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DIAGNOSTIC: Price Group Mappings on Production ===" -ForegroundColor Cyan
Write-Host ""

# Create diagnostic script on production
$diagnosticScript = @'
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ShopMapping;
use App\Models\PrestaShopShop;
use App\Models\PriceGroup;
use Illuminate\Support\Facades\DB;

echo "=== PRICE GROUP MAPPINGS DIAGNOSTIC ===\n\n";

$shop = PrestaShopShop::find(1);

if (!$shop) {
    echo "ERROR: Shop ID 1 not found\n";
    exit(1);
}

echo "Shop: {$shop->name} (ID: {$shop->id})\n\n";

// Check price group mappings
$mappings = ShopMapping::where('shop_id', $shop->id)
    ->where('mapping_type', ShopMapping::TYPE_PRICE_GROUP)
    ->where('is_active', true)
    ->get();

echo "Price Group Mappings: " . $mappings->count() . "\n";

if ($mappings->isEmpty()) {
    echo "CRITICAL: NO PRICE GROUP MAPPINGS FOUND!\n\n";
    echo "Available Price Groups:\n";
    $pgs = PriceGroup::where('is_active', true)->get();
    foreach ($pgs as $pg) {
        echo "  - ID: {$pg->id}, Code: {$pg->code}, Name: {$pg->name}\n";
    }
} else {
    echo "Mapped:\n";
    foreach ($mappings as $m) {
        $pg = PriceGroup::find((int)$m->ppm_value);
        if ($pg) {
            echo "  - PPM: {$m->ppm_value} ({$pg->code}) -> PS: {$m->prestashop_id}\n";
        }
    }
}

echo "\n";

// Check recent sync logs
echo "=== RECENT PRICE EXPORT LOGS ===\n";
$logFile = __DIR__ . '/storage/logs/laravel.log';
$cmd = "tail -500 '$logFile' | grep -A5 -B5 'PRICE EXPORT'";
system($cmd);
'@

# Upload diagnostic script
Write-Host "Uploading diagnostic script..." -ForegroundColor Yellow
echo $diagnosticScript | Out-File -FilePath "$env:TEMP\diagnostic_prices.php" -Encoding UTF8 -NoNewline
pscp -i $HostidoKey -P $HostidoPort "$env:TEMP\diagnostic_prices.php" "${HostidoHost}:${LaravelRoot}/diagnostic_prices.php" 2>&1 | Out-Null

# Run diagnostic
Write-Host "Running diagnostic on production..." -ForegroundColor Yellow
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $LaravelRoot && php diagnostic_prices.php"

# Cleanup
Write-Host ""
Write-Host "Cleaning up..." -ForegroundColor Yellow
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch "cd $LaravelRoot && rm diagnostic_prices.php"
Remove-Item "$env:TEMP\diagnostic_prices.php" -Force

Write-Host ""
Write-Host "Done." -ForegroundColor Green
