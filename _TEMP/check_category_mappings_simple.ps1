# Simple production check for category_mappings

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$Port = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== CHECKING category_mappings on PRODUCTION ===" -ForegroundColor Cyan
Write-Host ""

# Upload diagnostic script
$diagnosticScript = @'
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ProductShopData;

$samples = ProductShopData::whereNotNull('category_mappings')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get(['id', 'product_id', 'shop_id', 'category_mappings', 'sync_status', 'last_pulled_at']);

echo "TOTAL RECORDS WITH category_mappings: " . ProductShopData::whereNotNull('category_mappings')->count() . "\n\n";

foreach ($samples as $sample) {
    echo "=== Record ID: {$sample->id} ===\n";
    echo "Product: {$sample->product_id} | Shop: {$sample->shop_id} | Status: {$sample->sync_status}\n";
    echo "Last Pulled: {$sample->last_pulled_at}\n";
    echo "category_mappings (raw JSON): " . $sample->getRawOriginal('category_mappings') . "\n";

    $decoded = $sample->category_mappings;
    echo "category_mappings (decoded): " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";

    // Analyze format
    if (is_array($decoded)) {
        if (isset($decoded['selected']) || isset($decoded['primary'])) {
            echo "FORMAT: UI Format (has 'selected' or 'primary' keys)\n";
        } else {
            $keys = array_keys($decoded);
            $values = array_values($decoded);
            echo "FORMAT: Simple key-value array\n";
            echo "  Keys: " . implode(', ', $keys) . "\n";
            echo "  Values: " . implode(', ', $values) . "\n";

            // Check if keys == values
            if (count($keys) > 0) {
                $firstKey = $keys[0];
                $firstValue = $decoded[$firstKey];
                if ($firstKey == $firstValue) {
                    echo "  LIKELY: PrestaShop→PrestaShop (pullShopData format)\n";
                } else {
                    echo "  LIKELY: PPM→PrestaShop (CategoryMapper format)\n";
                }
            }
        }
    }

    echo "\n";
}
'@

# Save to temp file
$localScript = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TEMP\check_mappings.php"
$diagnosticScript | Out-File -FilePath $localScript -Encoding UTF8 -NoNewline

Write-Host "1. Uploading diagnostic script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $Port $localScript "${RemoteHost}:${RemotePath}/check_mappings.php" 2>$null

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ Upload successful" -ForegroundColor Green
    Write-Host ""

    Write-Host "2. Running diagnostic..." -ForegroundColor Yellow
    $result = plink -ssh $RemoteHost -P $Port -i $HostidoKey -batch "cd $RemotePath && php check_mappings.php"

    Write-Host $result
    Write-Host ""

    Write-Host "3. Cleaning up..." -ForegroundColor Yellow
    plink -ssh $RemoteHost -P $Port -i $HostidoKey -batch "cd $RemotePath && rm check_mappings.php" 2>$null
    Remove-Item $localScript -Force

    Write-Host "✓ Cleanup complete" -ForegroundColor Green
} else {
    Write-Host "✗ Upload failed" -ForegroundColor Red
}

Write-Host ""
Write-Host "=== DIAGNOSIS COMPLETE ===" -ForegroundColor Cyan
