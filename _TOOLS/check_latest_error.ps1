$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"
Write-Host "Checking VERY latest Laravel errors (last 10 lines)..." -ForegroundColor Cyan
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -n 10 $RemoteBase/storage/logs/laravel.log"
