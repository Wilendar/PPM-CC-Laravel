$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"
Write-Host "Checking latest 5 error lines..." -ForegroundColor Cyan
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep 'ERROR' $RemoteBase/storage/logs/laravel.log | tail -5"
