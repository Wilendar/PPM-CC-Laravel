$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "=== CHECKING FEATURE IMPORT LOGS ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && tail -200 storage/logs/laravel.log | grep -E 'FEATURE IMPORT' -A 2"
Write-Host "=== DONE ===" -ForegroundColor Green
