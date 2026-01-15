$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== VERIFYING getPrestaShopProductId FIX ===" -ForegroundColor Cyan

Write-Host "`n[1] Check for shopData() usage:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -n 'shopData' domains/ppm.mpptrade.pl/public_html/app/Services/Media/MediaSyncService.php"

Write-Host "`n[2] Show getPrestaShopProductId method:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "sed -n '413,425p' domains/ppm.mpptrade.pl/public_html/app/Services/Media/MediaSyncService.php"

Write-Host "`n=== DONE ===" -ForegroundColor Green
