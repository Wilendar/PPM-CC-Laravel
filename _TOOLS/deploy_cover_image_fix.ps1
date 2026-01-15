$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING COVER IMAGE FIX (2025-12-02) ===" -ForegroundColor Cyan

Write-Host "[1/2] Uploading PrestaShop8Client.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/PrestaShop/PrestaShop8Client.php" "$RemoteBase/app/Services/PrestaShop/PrestaShop8Client.php"

Write-Host "[2/2] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
