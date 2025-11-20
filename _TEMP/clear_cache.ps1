# Clear Laravel caches on production

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Clearing Laravel caches on production..." -ForegroundColor Cyan

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "Cache cleared successfully!" -ForegroundColor Green
} else {
    Write-Host "Cache clear failed with exit code: $LASTEXITCODE" -ForegroundColor Red
    exit $LASTEXITCODE
}
