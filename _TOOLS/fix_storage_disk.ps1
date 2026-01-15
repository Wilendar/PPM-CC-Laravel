$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== FIXING STORAGE DISK CONFIGURATION ===" -ForegroundColor Cyan

Write-Host "`n[1] Uploading fixed MediaStorageService..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/Media/MediaStorageService.php" "${RemoteBase}/app/Services/Media/MediaStorageService.php"

Write-Host "[2] Uploading fix script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "_TEMP/fix_storage_location.php" "${RemoteBase}/fix_storage_location.php"

Write-Host "[3] Running fix script..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php fix_storage_location.php"

Write-Host "[4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan view:clear"

Write-Host "[5] Cleanup fix script..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "rm domains/ppm.mpptrade.pl/public_html/fix_storage_location.php"

Write-Host "`n=== DONE ===" -ForegroundColor Green
