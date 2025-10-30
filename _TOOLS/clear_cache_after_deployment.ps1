# Clear Laravel Cache after Deployment

$ErrorActionPreference = "Stop"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`nClearing Laravel cache..." -ForegroundColor Cyan

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear && echo 'Cache cleared successfully'"

Write-Host "`n[OK] Cache cleared!" -ForegroundColor Green
