$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING MEDIA JOBS ===" -ForegroundColor Cyan
Write-Host ""

# 1. Create directory on remote
Write-Host "[1/3] Creating directories..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && mkdir -p app/Jobs/Media"

# 2. Upload Jobs
Write-Host "[2/3] Uploading Media Jobs..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/Media/SyncMediaFromPrestaShop.php" "${RemoteBase}/app/Jobs/Media/SyncMediaFromPrestaShop.php"
pscp -i $HostidoKey -P 64321 "app/Jobs/Media/ProcessMediaUpload.php" "${RemoteBase}/app/Jobs/Media/ProcessMediaUpload.php"
pscp -i $HostidoKey -P 64321 "app/Jobs/Media/PushMediaToPrestaShop.php" "${RemoteBase}/app/Jobs/Media/PushMediaToPrestaShop.php"
pscp -i $HostidoKey -P 64321 "app/Jobs/Media/BulkMediaUpload.php" "${RemoteBase}/app/Jobs/Media/BulkMediaUpload.php"

# 3. Clear cache
Write-Host "[3/3] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan queue:flush && php artisan config:clear"

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
