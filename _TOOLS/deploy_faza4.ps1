$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING FAZA 4 CHANGES ===" -ForegroundColor Cyan

Write-Host "[1/4] Uploading config/job_types.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "config/job_types.php" "${RemoteBase}/config/job_types.php"

Write-Host "[2/4] Uploading BulkSyncProducts.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Jobs/PrestaShop/BulkSyncProducts.php" "${RemoteBase}/app/Jobs/PrestaShop/BulkSyncProducts.php"

Write-Host "[3/4] Uploading JobProgress.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Models/JobProgress.php" "${RemoteBase}/app/Models/JobProgress.php"

Write-Host "[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear"

Write-Host "=== FAZA 4 DEPLOYMENT COMPLETE ===" -ForegroundColor Green
