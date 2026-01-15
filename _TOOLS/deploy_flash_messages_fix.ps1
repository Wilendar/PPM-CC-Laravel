# Deploy flash-messages width fix
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "[1/2] Uploading flash-messages.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/components/flash-messages.blade.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/components/flash-messages.blade.php"

Write-Host "[2/2] Clearing view cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear"

Write-Host "Done!" -ForegroundColor Green
