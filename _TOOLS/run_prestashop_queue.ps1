$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== RUNNING PRESTASHOP_SYNC QUEUE ===" -ForegroundColor Cyan

Write-Host "`n[1] Process prestashop_sync queue (timeout 120s):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && timeout 120 php artisan queue:work database --queue=prestashop_sync --stop-when-empty --verbose"

Write-Host "`n[2] Check SyncMedia logs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -100 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -iE 'MEDIA SYNC|download|pull|image' -A 1"

Write-Host "`n=== DONE ===" -ForegroundColor Green
