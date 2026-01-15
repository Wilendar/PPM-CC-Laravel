# Deploy Media Conflict Feature - ETAP_07d
# 2025-12-15
# Includes: MediaSyncService, SyncMediaFromPrestaShop, gallery-tab, CSS

$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== Deploy Media Conflict Feature ===" -ForegroundColor Cyan

# 1. Upload PHP files
Write-Host "`n[1/4] Uploading PHP files..." -ForegroundColor Yellow

$phpFiles = @(
    @{ Local = "app\Services\Media\MediaSyncService.php"; Remote = "app/Services/Media/MediaSyncService.php" },
    @{ Local = "app\Jobs\Media\SyncMediaFromPrestaShop.php"; Remote = "app/Jobs/Media/SyncMediaFromPrestaShop.php" },
    @{ Local = "resources\views\livewire\products\management\tabs\gallery-tab.blade.php"; Remote = "resources/views/livewire/products/management/tabs/gallery-tab.blade.php" }
)

foreach ($file in $phpFiles) {
    $localPath = Join-Path $LocalBase $file.Local
    $remotePath = "$RemoteBase/$($file.Remote)"
    Write-Host "  -> $($file.Local)" -ForegroundColor Gray
    pscp -i $HostidoKey -P 64321 "$localPath" "host379076@host379076.hostido.net.pl:$remotePath"
    if ($LASTEXITCODE -ne 0) { Write-Host "BLAD uploadu: $($file.Local)" -ForegroundColor Red; exit 1 }
}

# 2. Upload CSS assets
Write-Host "`n[2/4] Uploading CSS assets..." -ForegroundColor Yellow
$cssFiles = Get-ChildItem -Path "$LocalBase\public\build\assets\*.css"
foreach ($css in $cssFiles) {
    Write-Host "  -> $($css.Name)" -ForegroundColor Gray
    pscp -i $HostidoKey -P 64321 "$($css.FullName)" "host379076@host379076.hostido.net.pl:$RemoteBase/public/build/assets/$($css.Name)"
}

# 3. Upload manifest.json
Write-Host "`n[3/4] Uploading manifest.json..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\public\build\.vite\manifest.json" "host379076@host379076.hostido.net.pl:$RemoteBase/public/build/manifest.json"
if ($LASTEXITCODE -ne 0) { Write-Host "BLAD uploadu manifest" -ForegroundColor Red }

# 4. Clear cache
Write-Host "`n[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan cache:clear && php artisan config:clear && php artisan view:clear"

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Files deployed:" -ForegroundColor Cyan
Write-Host "  - MediaSyncService.php (orphan cleanup, shop conflict detection)" -ForegroundColor White
Write-Host "  - SyncMediaFromPrestaShop.php (conflict storage, better error logging)" -ForegroundColor White
Write-Host "  - gallery-tab.blade.php (conflict alert UI)" -ForegroundColor White
Write-Host "  - media-gallery.css (alert styles)" -ForegroundColor White
