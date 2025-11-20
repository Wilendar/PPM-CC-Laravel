$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== WHO OVERWROTE DATA? (11:29-11:32) ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "Searching for ProductShopData updates for product 11034, shop 1..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log" | Select-String -Pattern "11:29|11:30|11:31" | Select-String -Pattern "product.*11034|shop.*1|Pending changes saved|toggleCategory" -Context 1 | Select-Object -Last 60

Write-Host "`n=== DONE ===" -ForegroundColor Green
