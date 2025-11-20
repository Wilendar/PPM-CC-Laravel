$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== DIAGNOSING UI ISSUES ===" -ForegroundColor Red
Write-Host ""

Write-Host "1. Check recent logs for ProductCategoryManager usage:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -200 storage/logs/laravel.log" | Select-String -Pattern "ProductCategoryManager|CategoryManager initialized|loadCategories|loadShopCategories.*Loaded from" -Context 1

Write-Host "`n2. Check what UI component received:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -200 storage/logs/laravel.log" | Select-String -Pattern "Shop categories loaded.*Option A|final_shopCategories" -Context 1

Write-Host "`n3. Check redirect issue after save:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -200 storage/logs/laravel.log" | Select-String -Pattern "saveAndClose.*dispatched redirectToProductList|redirectToProductList event" -Context 1

Write-Host "`n=== DIAGNOSIS COMPLETE ===" -ForegroundColor Cyan
