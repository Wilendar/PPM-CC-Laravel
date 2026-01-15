$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING FEATURE SYNC FIX ===" -ForegroundColor Cyan

Write-Host "[1/2] Uploading HasFeatures.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Models\Concerns\Product\HasFeatures.php" "${RemoteBase}/app/Models/Concerns/Product/HasFeatures.php"

Write-Host "[2/2] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
