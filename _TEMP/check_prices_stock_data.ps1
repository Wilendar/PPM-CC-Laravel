# Check Prices & Stock Data on Production
# 2025-11-07

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== CHECKING PRICES & STOCK DATA ===" -ForegroundColor Cyan

Write-Host "[1] Price Groups count..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan tinker --execute='echo \\App\\Models\\PriceGroup::count();'"

Write-Host "`n[2] Warehouses count..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan tinker --execute='echo \\App\\Models\\Warehouse::count();'"

Write-Host "`n[3] Product Prices count..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan tinker --execute='echo \\App\\Models\\ProductPrice::count();'"

Write-Host "`n[4] Product Stock count..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan tinker --execute='echo \\App\\Models\\ProductStock::count();'"

Write-Host "`n=== CHECK COMPLETE ===" -ForegroundColor Green
