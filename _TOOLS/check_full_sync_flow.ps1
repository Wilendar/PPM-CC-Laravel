$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking full sync flow..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -500 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -A5 'MEDIA SYNC\|MEDIA REPLACE ALL\|selected_count\|IMAGE API'"
