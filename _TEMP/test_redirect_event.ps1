$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== TESTING REDIRECT AFTER SAVE ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "Checking logs for redirect event dispatch..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -100 storage/logs/laravel.log" | Select-String -Pattern "saveAndClose|dispatched redirectToProductList|All pending changes saved" -Context 1

Write-Host "`nIf you see 'dispatched redirectToProductList' above, the backend works." -ForegroundColor Gray
Write-Host "If redirect doesn't happen, it's a frontend JavaScript issue." -ForegroundColor Yellow
