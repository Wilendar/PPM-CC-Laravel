$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== DELETING BROKEN MEDIA AND RE-SYNCING ===" -ForegroundColor Cyan

Write-Host "`n[1] Uploading cleanup script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "_TEMP/cleanup_media.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/cleanup_media.php"

Write-Host "[2] Running cleanup script..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php cleanup_media.php"

Write-Host "[3] Processing queue..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:work --once --timeout=180"

Write-Host "[4] Checking logs..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -20 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(MEDIA|downloaded|skipped)'"

Write-Host "[5] Checking storage..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "ls -la domains/ppm.mpptrade.pl/public_html/storage/app/public/products/11103/ 2>/dev/null || echo 'Folder still not exists'"

Write-Host "[6] Cleanup..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "rm domains/ppm.mpptrade.pl/public_html/cleanup_media.php"

Write-Host "`n=== DONE ===" -ForegroundColor Green
