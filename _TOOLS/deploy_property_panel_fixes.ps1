$HostidoKey = "D:\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\Skrypty\PPM-CC-Laravel"

# Deploy PHP files
$files = @(
    "app/Services/VisualEditor/PropertyPanel/PropertyPanelService.php",
    "app/Services/VisualEditor/Blocks/PrestaShop/PrestashopSectionBlock.php",
    "app/Http/Livewire/Products/VisualDescription/Traits/UVE_PropertyPanel.php",
    "resources/views/livewire/products/visual-description/controls/list-settings.blade.php"
)

Write-Host "Deploying Property Panel fixes..." -ForegroundColor Cyan

foreach ($file in $files) {
    $localFile = Join-Path $LocalPath $file
    $remoteDest = "${RemotePath}/${file}"
    Write-Host "Uploading: $file" -ForegroundColor Yellow
    pscp -i $HostidoKey -P 64321 $localFile "host379076@host379076.hostido.net.pl:$remoteDest"
    if ($LASTEXITCODE -eq 0) {
        Write-Host "  OK" -ForegroundColor Green
    } else {
        Write-Host "  FAILED" -ForegroundColor Red
    }
}

# Deploy assets
Write-Host "`nDeploying assets..." -ForegroundColor Cyan
pscp -i $HostidoKey -P 64321 -r "$LocalPath/public/build/assets/*" "host379076@host379076.hostido.net.pl:${RemotePath}/public/build/assets/"
pscp -i $HostidoKey -P 64321 "$LocalPath/public/build/.vite/manifest.json" "host379076@host379076.hostido.net.pl:${RemotePath}/public/build/manifest.json"

# Clear cache
Write-Host "`nClearing cache..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd ${RemotePath} && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "`nDeployment complete!" -ForegroundColor Green
