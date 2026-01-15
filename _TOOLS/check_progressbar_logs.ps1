# Check JobProgressBar logs
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking for JobProgressBar logs..." -ForegroundColor Cyan
$cmd = "tail -50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E 'JobProgressBar'"
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cmd
