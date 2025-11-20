$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING CATEGORY SAVE LOGS (Last Test Run) ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "Timestamp: Last test run around 11:30-11:32" -ForegroundColor Yellow
Write-Host ""

Write-Host "STEP 1: Check saveAndClose logs" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log" | Select-String -Pattern "saveAndClose|All pending changes saved" -Context 2 | Select-Object -Last 20

Write-Host "`n`nSTEP 2: Check savePendingChangesToShop logs (category specific)" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log" | Select-String -Pattern "savePendingChangesToShop|category|Checking category sync condition" -Context 1 | Select-Object -Last 30

Write-Host "`n`nSTEP 3: Check ProductShopData updates" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log" | Select-String -Pattern "ProductShopData.*updated|Saved.*ProductShopData" -Context 2 | Select-Object -Last 20

Write-Host "`n`n=== DONE ===" -ForegroundColor Green
