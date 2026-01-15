$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"
Write-Host "Checking latest error timestamps..." -ForegroundColor Cyan
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -E '^\[2025-12-11' $RemoteBase/storage/logs/laravel.log | tail -5"
