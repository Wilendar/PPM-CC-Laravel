# Deploy Pending Shop Changes Architecture
# ETAP_07d: Deferred sync for GalleryTab
# Date: 2025-12-01

$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$RemoteRoot = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== DEPLOY: Pending Shop Changes Architecture ===" -ForegroundColor Cyan
Write-Host "Target: GalleryTab.php + gallery-tab.blade.php + media-gallery.css`n" -ForegroundColor Yellow

# Files to deploy
$files = @(
    @{
        Local = "app/Http/Livewire/Products/Management/Tabs/GalleryTab.php"
        Remote = "$RemoteRoot/app/Http/Livewire/Products/Management/Tabs/GalleryTab.php"
    },
    @{
        Local = "resources/views/livewire/products/management/tabs/gallery-tab.blade.php"
        Remote = "$RemoteRoot/resources/views/livewire/products/management/tabs/gallery-tab.blade.php"
    },
    @{
        Local = "resources/css/products/media-gallery.css"
        Remote = "$RemoteRoot/resources/css/products/media-gallery.css"
    }
)

# Upload files
Write-Host "[1/4] Uploading files..." -ForegroundColor Green
foreach ($file in $files) {
    Write-Host "  - Uploading $($file.Local)..." -ForegroundColor Gray
    pscp -i $HostidoKey -P $HostidoPort $file.Local "${HostidoHost}:$($file.Remote)"
    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Failed to upload $($file.Local)" -ForegroundColor Red
        exit 1
    }
}

# Build assets locally
Write-Host "`n[2/4] Building assets..." -ForegroundColor Green
npm run build
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Build failed" -ForegroundColor Red
    exit 1
}

# Upload built CSS
Write-Host "`n[3/4] Uploading built CSS..." -ForegroundColor Green
$cssFile = Get-ChildItem "public/build/assets/media-gallery-*.css" | Select-Object -First 1
if ($cssFile) {
    Write-Host "  - Uploading $($cssFile.Name)..." -ForegroundColor Gray
    pscp -i $HostidoKey -P $HostidoPort $cssFile.FullName "${HostidoHost}:${RemoteRoot}/public/build/assets/$($cssFile.Name)"

    # Upload manifest
    Write-Host "  - Uploading manifest..." -ForegroundColor Gray
    pscp -i $HostidoKey -P $HostidoPort "public/build/.vite/manifest.json" "${HostidoHost}:${RemoteRoot}/public/build/manifest.json"
} else {
    Write-Host "WARNING: media-gallery CSS not found in build output" -ForegroundColor Yellow
}

# Clear cache
Write-Host "`n[4/4] Clearing cache..." -ForegroundColor Green
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch @"
cd $RemoteRoot && php artisan view:clear && php artisan cache:clear && php artisan config:clear
"@

Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Test URL: https://ppm.mpptrade.pl/admin/products" -ForegroundColor Cyan
Write-Host "`nEXPECTED BEHAVIOR:" -ForegroundColor Yellow
Write-Host "  1. Kliknij checkbox sklepu pod zdjeciem → NIE robi API call" -ForegroundColor White
Write-Host "  2. Checkbox dostaje zolte obramowanie + pulsowanie + '⏳'" -ForegroundColor White
Write-Host "  3. Pojawia sie przycisk 'Zastosuj zmiany synchronizacji (N)'" -ForegroundColor White
Write-Host "  4. Klikniecie przycisku → WTEDY API calls do PrestaShop" -ForegroundColor White
Write-Host "  5. Po zakonczeniu → pulsowanie znika, stan zsynchronizowany" -ForegroundColor White
