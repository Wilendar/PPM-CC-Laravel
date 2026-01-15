# Deploy buildUrl debug fix
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING buildUrl DEBUG FIX ===" -ForegroundColor Cyan

Write-Host "[1/3] Uploading BasePrestaShopClient.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/PrestaShop/BasePrestaShopClient.php" "${RemoteBase}/app/Services/PrestaShop/BasePrestaShopClient.php"

Write-Host "[2/3] Clearing cache (opcache + Laravel)..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear && php artisan view:clear"

Write-Host "[3/3] Resetting OPcache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php -r 'if (function_exists(\"opcache_reset\")) { opcache_reset(); echo \"OPcache reset\"; } else { echo \"OPcache not available\"; }'"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
