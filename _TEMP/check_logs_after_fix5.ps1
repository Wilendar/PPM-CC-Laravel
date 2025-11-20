$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   CHECKING LOGS AFTER FIX #5 (last 3 minutes)" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

Write-Host "📋 Looking for 'saveAndClose called':`n" -ForegroundColor White

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -300 storage/logs/laravel.log | grep -E 'saveAndClose called' -A 5"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
