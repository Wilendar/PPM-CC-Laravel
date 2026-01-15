$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== VERIFYING MEDIASYNCSERVICE ON SERVER ===" -ForegroundColor Cyan

Write-Host "`n[1] Check for PrestaShopPrestaShopShop (BAD):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -c 'PrestaShopPrestaShopShop' domains/ppm.mpptrade.pl/public_html/app/Services/Media/MediaSyncService.php || echo '0 occurrences - GOOD!'"

Write-Host "`n[2] Check for PrestaShopShop (GOOD):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -c 'PrestaShopShop' domains/ppm.mpptrade.pl/public_html/app/Services/Media/MediaSyncService.php"

Write-Host "`n[3] Show pullFromPrestaShop signature:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "grep -n 'function pullFromPrestaShop' domains/ppm.mpptrade.pl/public_html/app/Services/Media/MediaSyncService.php"

Write-Host "`n=== DONE ===" -ForegroundColor Green
