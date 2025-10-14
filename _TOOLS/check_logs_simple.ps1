# Simple log checker

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostName = "host379076@host379076.hostido.net.pl"
$Port = 64321

Write-Host "=== Recent Laravel Logs ===" -ForegroundColor Cyan

plink -ssh $HostName -P $Port -i $HostidoKey -batch "tail -100 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"

Write-Host "`n=== End of Logs ===" -ForegroundColor Green
