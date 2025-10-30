# Force cache clear with opcache reset
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== FORCE CACHE CLEAR ===" -ForegroundColor Cyan

plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch `
  "cd ${RemotePath} && php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan optimize:clear && echo '=== ALL CACHES CLEARED ==='"

Write-Host "`nWaiting 10 seconds for propagation..." -ForegroundColor Yellow
Start-Sleep -Seconds 10
Write-Host "Ready!" -ForegroundColor Green
