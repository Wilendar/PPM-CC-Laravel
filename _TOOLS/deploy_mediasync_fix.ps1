$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING MEDIASYNCSERVICE FIX ===" -ForegroundColor Cyan

Write-Host "`n[1/3] Uploading MediaSyncService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/Media/MediaSyncService.php" "${RemoteBase}/app/Services/Media/MediaSyncService.php"

Write-Host "`n[2/3] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan queue:flush"

Write-Host "`n[3/3] Processing prestashop_sync queue..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && timeout 60 php artisan queue:work database --queue=prestashop_sync --stop-when-empty --verbose"

Write-Host "`n=== DONE ===" -ForegroundColor Green
