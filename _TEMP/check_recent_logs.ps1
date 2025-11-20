$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "   CHECKING RECENT LARAVEL LOGS" -ForegroundColor Yellow
Write-Host "═══════════════════════════════════════════════════════════════`n" -ForegroundColor Cyan

Write-Host "📋 Last 50 log entries (filtered for save operations):`n" -ForegroundColor White

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -100 storage/logs/laravel.log | grep -E 'saveAndClose|savePendingChanges|Error saving|ETAP_07b|validation.failed|ErrorBag'"

Write-Host "`n═══════════════════════════════════════════════════════════════" -ForegroundColor Cyan
