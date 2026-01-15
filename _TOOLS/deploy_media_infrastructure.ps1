$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING MEDIA INFRASTRUCTURE ===" -ForegroundColor Cyan
Write-Host ""

# 1. Create directories on remote
Write-Host "[1/5] Creating directories..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && mkdir -p app/DTOs/Media && mkdir -p app/Events/Media && mkdir -p app/Services/Media"

# 2. Upload DTOs
Write-Host "[2/5] Uploading DTOs..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/DTOs/Media/MediaSyncStatusDTO.php" "${RemoteBase}/app/DTOs/Media/MediaSyncStatusDTO.php"
pscp -i $HostidoKey -P 64321 "app/DTOs/Media/MediaUploadDTO.php" "${RemoteBase}/app/DTOs/Media/MediaUploadDTO.php"

# 3. Upload Events
Write-Host "[3/5] Uploading Events..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Events/Media/MediaSyncCompleted.php" "${RemoteBase}/app/Events/Media/MediaSyncCompleted.php"
pscp -i $HostidoKey -P 64321 "app/Events/Media/MediaUploaded.php" "${RemoteBase}/app/Events/Media/MediaUploaded.php"
pscp -i $HostidoKey -P 64321 "app/Events/Media/MediaDeleted.php" "${RemoteBase}/app/Events/Media/MediaDeleted.php"

# 4. Upload Services
Write-Host "[4/5] Uploading MediaSyncService and MediaStorageService..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/Media/MediaSyncService.php" "${RemoteBase}/app/Services/Media/MediaSyncService.php"
pscp -i $HostidoKey -P 64321 "app/Services/Media/MediaStorageService.php" "${RemoteBase}/app/Services/Media/MediaStorageService.php"

# 5. Clear cache
Write-Host "[5/5] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan queue:flush && php artisan config:clear"

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
