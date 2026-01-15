$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== CHECKING COMPAT SYNC LOGS ===" -ForegroundColor Cyan

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -100 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"
