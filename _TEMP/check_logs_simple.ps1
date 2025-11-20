$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING LOGS FOR REDIRECT ===" -ForegroundColor Cyan

Write-Host "`nSearching for 'saveAndClose', 'redirect', 'dispatched':" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -200 storage/logs/laravel.log" | Select-String -Pattern "saveAndClose|redirect|dispatched" -Context 2

Write-Host "`n=== DONE ===" -ForegroundColor Green
