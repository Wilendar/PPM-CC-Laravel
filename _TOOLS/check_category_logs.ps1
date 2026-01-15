# Check latest API request logs
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING LATEST API LOGS ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -30 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -i 'categories\|api.*request'"
