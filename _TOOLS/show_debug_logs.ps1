# Show debug logs after visiting product page

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostName = "host379076@host379076.hostido.net.pl"
$Port = 64321

Write-Host "`n===============================================" -ForegroundColor Cyan
Write-Host " DEBUG LOGS - Per-Shop Categories Loading" -ForegroundColor Cyan
Write-Host "===============================================`n" -ForegroundColor Cyan

Write-Host "Fetching recent logs..." -ForegroundColor Yellow

plink -ssh $HostName -P $Port -i $HostidoKey -batch "tail -300 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(loadProductData|loadShopCategories|shopCategories_after|product_id.:10958)' | tail -50"

Write-Host "`n===============================================" -ForegroundColor Green
Write-Host " Logs Displayed" -ForegroundColor Green
Write-Host "===============================================`n" -ForegroundColor Green
