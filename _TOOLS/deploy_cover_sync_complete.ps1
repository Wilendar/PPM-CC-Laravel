$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING COVER SYNC COMPLETE FIX (2025-12-02) ===" -ForegroundColor Cyan

Write-Host "[1/3] Uploading PrestaShop8Client.php (PATCH fix)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/PrestaShop/PrestaShop8Client.php" "$RemoteBase/app/Services/PrestaShop/PrestaShop8Client.php"

Write-Host "[2/3] Uploading ProductSyncStrategy.php (cover sync logic)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/PrestaShop/Sync/ProductSyncStrategy.php" "$RemoteBase/app/Services/PrestaShop/Sync/ProductSyncStrategy.php"

Write-Host "[3/3] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
