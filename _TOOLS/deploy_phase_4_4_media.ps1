$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING PHASE 4.4 MEDIA INTEGRATION ===" -ForegroundColor Cyan

# 1. Deploy PHP Services
Write-Host "[1/6] Uploading MediaSyncService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/Media/MediaSyncService.php" "${RemoteBase}/app/Services/Media/MediaSyncService.php"

Write-Host "[2/6] Uploading ProductSyncStrategy.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/PrestaShop/Sync/ProductSyncStrategy.php" "${RemoteBase}/app/Services/PrestaShop/Sync/ProductSyncStrategy.php"

Write-Host "[3/6] Uploading PushMediaToPrestaShop.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/Media/PushMediaToPrestaShop.php" "${RemoteBase}/app/Jobs/Media/PushMediaToPrestaShop.php"

# 2. Deploy ALL assets (Vite regenerates hashes for all files)
Write-Host "[4/6] Uploading ALL assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "${RemoteBase}/public/build/assets/"

# 3. Deploy manifest to ROOT (CRITICAL!)
Write-Host "[5/6] Uploading manifest to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "${RemoteBase}/public/build/manifest.json"

# 4. Clear cache
Write-Host "[6/6] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
