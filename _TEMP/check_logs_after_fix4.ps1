$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   CHECKING LOGS AFTER FIX #4 (last 2 minutes)" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

Write-Host "📋 Recent logs:`n" -ForegroundColor White

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -200 storage/logs/laravel.log | grep -E '202[45]-11-20 09:(5[0-9]|6[0-9]|[0-9]{2}:[0-9]{2}:[0-9]{2})' | head -50"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
