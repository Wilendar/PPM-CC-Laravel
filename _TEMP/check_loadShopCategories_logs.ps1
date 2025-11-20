$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING loadShopCategories LOGS ===" -ForegroundColor Cyan

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log" | Select-String -Pattern "loadShopCategories|Loaded from product_shop_data|Shop categories loaded" -Context 2 | Select-Object -Last 30

Write-Host "`n=== DONE ===" -ForegroundColor Green
