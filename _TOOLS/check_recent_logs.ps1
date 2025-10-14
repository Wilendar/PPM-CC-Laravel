# Check recent Laravel logs for loadShopCategories debug messages

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostName = "host379076@host379076.hostido.net.pl"
$Port = 64321

Write-Host "`n=== Checking recent Laravel logs for product 10957 ===" -ForegroundColor Cyan
Write-Host "Please visit: https://ppm.mpptrade.pl/admin/products/10957/edit" -ForegroundColor Yellow
Write-Host "Then press Enter to see logs..." -ForegroundColor Yellow
Read-Host

Write-Host "`n=== Log Output ===" -ForegroundColor Green

$command = "tail -n 200 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(loadShopCategories|Shop categories loaded|product_id.:10957)' | tail -n 50"

plink -ssh $HostName -P $Port -i $HostidoKey -batch $command

Write-Host "`n=== End of Logs ===" -ForegroundColor Green
