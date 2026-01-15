$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking full logs for saveAndClose..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -200 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(saveAndClose|SAVE|pending_delete|Starting physical)' -A 2"
