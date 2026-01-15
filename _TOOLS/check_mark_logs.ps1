$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking logs for mark category..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(MARK|markCategory|pendingDelete)' -A 1"
