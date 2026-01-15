# Deploy ETAP_07f_P3: CSS/JS Editor System
# Deploys new CSS/JS discovery and editor modal to production
# Date: 2025-12-17

$ErrorActionPreference = "Stop"

# SSH Configuration
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "=== ETAP_07f_P3: CSS/JS Editor System Deployment ===" -ForegroundColor Cyan
Write-Host ""

# Files to deploy
$files = @(
    # Services
    "app/Services/VisualEditor/PrestaShopAssetDiscovery.php",
    "app/Services/VisualEditor/PrestaShopCssFetcher.php",

    # Model
    "app/Models/PrestaShopShop.php",
    "app/Models/BlockDefinition.php",

    # Migrations
    "database/migrations/2025_12_17_100001_add_css_cache_columns_to_prestashop_shops.php",
    "database/migrations/2025_12_17_100002_create_block_definitions_table.php",

    # Livewire Components
    "app/Http/Livewire/Products/VisualDescription/CssJsEditorModal.php",
    "app/Http/Livewire/Products/VisualDescription/VisualDescriptionEditor.php",

    # Views
    "resources/views/livewire/products/visual-description/partials/css-js-editor-modal.blade.php",
    "resources/views/livewire/products/visual-description/visual-description-editor.blade.php"
)

Write-Host "Deploying files..." -ForegroundColor Yellow

foreach ($file in $files) {
    $localFile = Join-Path $LocalPath $file
    $remoteFile = "$RemotePath/$file"

    if (Test-Path $localFile) {
        Write-Host "  -> $file" -ForegroundColor Gray
        pscp -i $HostidoKey -P $RemotePort $localFile "${RemoteHost}:$remoteFile" 2>&1 | Out-Null
        if ($LASTEXITCODE -ne 0) {
            Write-Host "    ERROR deploying $file" -ForegroundColor Red
        }
    } else {
        Write-Host "    FILE NOT FOUND: $localFile" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Running migrations..." -ForegroundColor Yellow

$migrateCmd = "cd $RemotePath && php artisan migrate --force 2>&1"
$result = plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch $migrateCmd
Write-Host $result

Write-Host ""
Write-Host "Clearing cache..." -ForegroundColor Yellow

$clearCmd = "cd $RemotePath && php artisan view:clear && php artisan cache:clear && php artisan config:clear && composer dump-autoload 2>&1"
$result = plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch $clearCmd
Write-Host $result

Write-Host ""
Write-Host "=== Deployment Complete ===" -ForegroundColor Green
Write-Host ""
Write-Host "Test the CSS/JS Editor at:" -ForegroundColor Cyan
Write-Host "https://ppm.mpptrade.pl/products/{productId}/visual-editor/{shopId}" -ForegroundColor White
Write-Host ""
Write-Host "Click the CSS/JS button in the header to open the new editor modal." -ForegroundColor Gray
