$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$SshCmd = "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate:status"

Write-Host "Checking migration status..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $SshCmd
