$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking media sync logs for fix verification..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -300 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(MEDIA SYNC|MEDIA REPLACE|Capturing pending|pending_changes)' -A 2"
