$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING REPLACE ALL STRATEGY FOR MEDIA SYNC ===" -ForegroundColor Cyan

Write-Host "[1/4] Uploading PrestaShop8Client.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/PrestaShop/PrestaShop8Client.php" "$RemoteBase/app/Services/PrestaShop/PrestaShop8Client.php"

Write-Host "[2/4] Uploading MediaSyncService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/Media/MediaSyncService.php" "$RemoteBase/app/Services/Media/MediaSyncService.php"

Write-Host "[3/4] Uploading ProductSyncStrategy.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/PrestaShop/Sync/ProductSyncStrategy.php" "$RemoteBase/app/Services/PrestaShop/Sync/ProductSyncStrategy.php"

Write-Host "[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
