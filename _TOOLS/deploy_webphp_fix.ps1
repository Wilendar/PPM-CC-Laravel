$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying web.php route fixes" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Upload web.php
Write-Host "`n[UPLOADING] routes/web.php" -ForegroundColor Yellow
& pscp -i $HostidoKey -P 64321 "$LocalBase\routes\web.php" "host379076@host379076.hostido.net.pl:$RemoteBase/routes/web.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] web.php uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Failed to upload" -ForegroundColor Red
    exit 1
}

# Clear ALL caches including bootstrap
Write-Host "`n[CACHE] Clearing ALL caches..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && rm -rf bootstrap/cache/*.php && php artisan optimize:clear"

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "DONE! Test URL: https://ppm.mpptrade.pl/admin/products/11148/edit" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
