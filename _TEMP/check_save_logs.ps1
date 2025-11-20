$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   CHECKING SAVE LOGS (last test run ~09:41)" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

Write-Host "📋 Logs from last 5 minutes:`n" -ForegroundColor White

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -300 storage/logs/laravel.log | grep -E '202[45]-11-20 09:(3[6-9]|4[0-5])' | grep -E 'saveAndClose|savePendingChanges|Error|validation|ErrorBag|redirect'"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
