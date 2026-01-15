$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking full media sync logs..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -400 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(MEDIA|IMAGE API|replaceAll|deleteAll|pending_media_sync)'"
