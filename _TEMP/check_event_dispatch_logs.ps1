$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   CHECKING EVENT DISPATCH LOGS" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

Write-Host "📋 Looking for 'redirectToProductList event':`n" -ForegroundColor White

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -300 storage/logs/laravel.log | grep -E 'redirectToProductList|saveAndClose dispatched' -A 2"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
