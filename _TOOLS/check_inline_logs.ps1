# Check inline category creation logs
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Checking Laravel logs for INLINE CREATE..." -ForegroundColor Cyan
$output = plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -200 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E 'INLINE|pending|DEFERRED' -i"
$output
