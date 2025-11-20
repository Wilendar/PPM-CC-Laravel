$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING saveAndClose LOGS ===" -ForegroundColor Cyan

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -300 storage/logs/laravel.log" | Select-String -Pattern "saveAndClose|redirectToProductList" -Context 5

Write-Host "`n=== DONE ===" -ForegroundColor Green
