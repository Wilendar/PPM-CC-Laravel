$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"
Write-Host "Checking errors after 13:48..." -ForegroundColor Cyan
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -E '^\[2025-12-11 (13:(4[8-9]|5[0-9])|14:)' $RemoteBase/storage/logs/laravel.log"
