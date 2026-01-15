# Deploy media job_id fix for sync connection jobs
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING MEDIA JOB ID FIX ===" -ForegroundColor Cyan

Write-Host "[1/3] Uploading SyncMediaFromPrestaShop.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/Media/SyncMediaFromPrestaShop.php" "${RemoteBase}/app/Jobs/Media/SyncMediaFromPrestaShop.php"

Write-Host "[2/3] Uploading PushMediaToPrestaShop.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/Media/PushMediaToPrestaShop.php" "${RemoteBase}/app/Jobs/Media/PushMediaToPrestaShop.php"

Write-Host "[3/3] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
