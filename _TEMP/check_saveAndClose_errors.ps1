$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   CHECKING saveAndClose ERRORS" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

Write-Host "📋 Recent saveAndClose calls with context:`n" -ForegroundColor White

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -500 storage/logs/laravel.log | grep -E 'saveAndClose called|ERROR|CRITICAL' | tail -30"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
