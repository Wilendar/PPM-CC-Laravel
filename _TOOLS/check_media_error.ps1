# Check Laravel logs for media error
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Checking Laravel logs..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -80 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(Error|Exception|media|MediaManager)' -A 3"
