$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== RUNNING QUEUE WORKER ===" -ForegroundColor Cyan

Write-Host "`n[1] Process pending jobs (timeout 90s):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && timeout 90 php artisan queue:work --stop-when-empty --verbose"

Write-Host "`n[2] Check SyncMedia logs:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -80 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -iE 'SyncMedia|image|download|Processed'"

Write-Host "`n=== DONE ===" -ForegroundColor Green
