$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING MEDIA SYSTEM (Jobs + Services) ===" -ForegroundColor Cyan

# 1. Create remote directories if not exist
Write-Host "[1/8] Creating remote directories..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && mkdir -p app/Jobs/Media && mkdir -p app/Services/Media"

# 2. Upload Media Jobs
Write-Host "[2/8] Uploading SyncMediaFromPrestaShop.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/Media/SyncMediaFromPrestaShop.php" "${RemoteBase}/app/Jobs/Media/SyncMediaFromPrestaShop.php"

Write-Host "[3/8] Uploading ProcessMediaUpload.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/Media/ProcessMediaUpload.php" "${RemoteBase}/app/Jobs/Media/ProcessMediaUpload.php"

Write-Host "[4/8] Uploading BulkMediaUpload.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/Media/BulkMediaUpload.php" "${RemoteBase}/app/Jobs/Media/BulkMediaUpload.php"

Write-Host "[5/8] Uploading PushMediaToPrestaShop.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/Media/PushMediaToPrestaShop.php" "${RemoteBase}/app/Jobs/Media/PushMediaToPrestaShop.php"

# 3. Upload Media Services
Write-Host "[6/8] Uploading MediaStorageService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/Media/MediaStorageService.php" "${RemoteBase}/app/Services/Media/MediaStorageService.php"

Write-Host "[7/8] Uploading remaining Media Services..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/Media/MediaManager.php" "${RemoteBase}/app/Services/Media/MediaManager.php"
pscp -i $HostidoKey -P 64321 "app/Services/Media/ImageProcessor.php" "${RemoteBase}/app/Services/Media/ImageProcessor.php"
pscp -i $HostidoKey -P 64321 "app/Services/Media/MediaSyncService.php" "${RemoteBase}/app/Services/Media/MediaSyncService.php"

# 4. Clear cache and restart queue
Write-Host "[8/8] Clearing cache and restarting queue..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear && php artisan queue:restart"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host ""
Write-Host "DEPLOYED FILES:" -ForegroundColor Cyan
Write-Host "- app/Jobs/Media/SyncMediaFromPrestaShop.php" -ForegroundColor White
Write-Host "- app/Jobs/Media/ProcessMediaUpload.php" -ForegroundColor White
Write-Host "- app/Jobs/Media/BulkMediaUpload.php" -ForegroundColor White
Write-Host "- app/Jobs/Media/PushMediaToPrestaShop.php" -ForegroundColor White
Write-Host "- app/Services/Media/MediaStorageService.php" -ForegroundColor White
Write-Host "- app/Services/Media/MediaManager.php" -ForegroundColor White
Write-Host "- app/Services/Media/ImageProcessor.php" -ForegroundColor White
Write-Host "- app/Services/Media/MediaSyncService.php" -ForegroundColor White
