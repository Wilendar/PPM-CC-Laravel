$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "Uploading diagnostic script..." -ForegroundColor Cyan
pscp -i $HostidoKey -P 64321 -q "_TEMP/analyze_quantity_sync.php" "$RemoteBase/_TEMP/analyze_quantity_sync.php"

Write-Host "Running analysis..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker < _TEMP/analyze_quantity_sync.php"
