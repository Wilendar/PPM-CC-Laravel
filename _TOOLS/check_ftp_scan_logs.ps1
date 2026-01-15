$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"

Write-Host "Checking FTP scan logs..." -ForegroundColor Cyan
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "tail -100 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(FTP|CSS.JS|fallback|scanFiles)'"
