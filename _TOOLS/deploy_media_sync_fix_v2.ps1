# Deploy MediaSyncService.php z poprawiona logika importu zdjec
# 2025-12-15

$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== Deploy MediaSyncService.php ===" -ForegroundColor Cyan

# Upload file
Write-Host "Uploading MediaSyncService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Services\Media\MediaSyncService.php" "host379076@host379076.hostido.net.pl:$RemoteBase/app/Services/Media/MediaSyncService.php"

if ($LASTEXITCODE -ne 0) {
    Write-Host "BLAD: Upload failed" -ForegroundColor Red
    exit 1
}

Write-Host "Upload OK" -ForegroundColor Green

# Clear cache
Write-Host "Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan cache:clear && php artisan config:clear && php artisan view:clear"

if ($LASTEXITCODE -ne 0) {
    Write-Host "OSTRZEZENIE: Cache clear may have failed" -ForegroundColor Yellow
} else {
    Write-Host "Cache cleared OK" -ForegroundColor Green
}

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
