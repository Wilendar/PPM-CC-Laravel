# ETAP_07f Faza 8.2: PrestaShop Description Sync Deployment
# Visual Description Editor - PrestaShop Integration

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

# SSH Configuration
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ETAP_07f Faza 8.2: PrestaShop Description Sync" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Files to deploy
$files = @(
    @{
        Local = "app\Models\ProductDescription.php"
        Remote = "app/Models/ProductDescription.php"
        Description = "ProductDescription model with sync methods"
    },
    @{
        Local = "app\Services\PrestaShop\ProductTransformer.php"
        Remote = "app/Services/PrestaShop/ProductTransformer.php"
        Description = "ProductTransformer with visual description integration"
    },
    @{
        Local = "app\Services\PrestaShop\Sync\ProductSyncStrategy.php"
        Remote = "app/Services/PrestaShop/Sync/ProductSyncStrategy.php"
        Description = "ProductSyncStrategy with markVisualDescriptionAsSynced"
    },
    @{
        Local = "database\migrations\2025_12_11_120001_add_sync_settings_to_product_descriptions.php"
        Remote = "database/migrations/2025_12_11_120001_add_sync_settings_to_product_descriptions.php"
        Description = "Migration for sync_settings columns"
    }
)

Write-Host "[1/4] Deploying PHP files..." -ForegroundColor Yellow

foreach ($file in $files) {
    $localFile = Join-Path $LocalPath $file.Local
    $remoteFile = "$RemotePath/$($file.Remote)"

    if (Test-Path $localFile) {
        Write-Host "  -> $($file.Description)" -ForegroundColor Gray
        & pscp -i $HostidoKey -P $RemotePort $localFile "${RemoteHost}:${remoteFile}"
        if ($LASTEXITCODE -eq 0) {
            Write-Host "     OK: $($file.Local)" -ForegroundColor Green
        } else {
            Write-Host "     FAILED: $($file.Local)" -ForegroundColor Red
            exit 1
        }
    } else {
        Write-Host "     NOT FOUND: $localFile" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "[2/4] Running migration..." -ForegroundColor Yellow

$migrateCmd = "cd $RemotePath && php artisan migrate --force 2>&1"
$migrateOutput = & plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch $migrateCmd

Write-Host $migrateOutput

if ($migrateOutput -match "error" -or $migrateOutput -match "SQLSTATE") {
    Write-Host "  Migration may have issues - check output above" -ForegroundColor Yellow
} else {
    Write-Host "  Migration completed" -ForegroundColor Green
}

Write-Host ""
Write-Host "[3/4] Clearing caches..." -ForegroundColor Yellow

$cacheCmd = "cd $RemotePath && php artisan cache:clear && php artisan config:clear && php artisan view:clear && composer dump-autoload -o 2>&1"
$cacheOutput = & plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch $cacheCmd

Write-Host "  Cache cleared" -ForegroundColor Green

Write-Host ""
Write-Host "[4/4] Verifying deployment..." -ForegroundColor Yellow

# Verify ProductDescription has new methods
$verifyCmd = "cd $RemotePath && php -r `"require 'vendor/autoload.php'; `\$r = new ReflectionClass('App\Models\ProductDescription'); echo 'Methods: '; echo implode(', ', array_map(fn(`\$m) => `\$m->name, array_filter(`\$r->getMethods(), fn(`\$m) => `\$m->class === 'App\Models\ProductDescription' && str_contains(`\$m->name, 'Sync')))); echo PHP_EOL;`" 2>&1"
$verifyOutput = & plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch $verifyCmd

Write-Host "  $verifyOutput" -ForegroundColor Cyan

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "DEPLOYMENT COMPLETED!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Deployed components:" -ForegroundColor White
Write-Host "  - ProductDescription model with sync methods" -ForegroundColor Gray
Write-Host "  - ProductTransformer with visual description integration" -ForegroundColor Gray
Write-Host "  - ProductSyncStrategy with markVisualDescriptionAsSynced" -ForegroundColor Gray
Write-Host "  - Migration for sync_settings columns" -ForegroundColor Gray
Write-Host ""
Write-Host "New database columns (product_descriptions):" -ForegroundColor White
Write-Host "  - sync_to_prestashop (boolean, default: true)" -ForegroundColor Gray
Write-Host "  - target_field (enum: description, description_short, both)" -ForegroundColor Gray
Write-Host "  - include_inline_css (boolean, default: true)" -ForegroundColor Gray
Write-Host "  - last_synced_at (timestamp, nullable)" -ForegroundColor Gray
Write-Host "  - sync_checksum (string, nullable)" -ForegroundColor Gray
Write-Host ""
Write-Host "To test:" -ForegroundColor Yellow
Write-Host "  1. Create a visual description for a product" -ForegroundColor Gray
Write-Host "  2. Sync the product to PrestaShop" -ForegroundColor Gray
Write-Host "  3. Check PrestaShop product description field" -ForegroundColor Gray
