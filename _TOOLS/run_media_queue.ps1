$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== RUNNING MEDIA SYNC QUEUE ===" -ForegroundColor Cyan

Write-Host "`n[1] Check pending jobs in queue:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan queue:size prestashop_sync"

Write-Host "`n[2] Process prestashop_sync queue (timeout 120s):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && timeout 120 php artisan queue:work database --queue=prestashop_sync --stop-when-empty --verbose 2>&1"

Write-Host "`n[3] Check latest media sync logs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -30 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -iE '(MEDIA|image|download)'"

Write-Host "`n=== DONE ===" -ForegroundColor Green
