$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== REFRESHING COMPOSER AUTOLOADER ===" -ForegroundColor Cyan

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && composer dump-autoload"

Write-Host "=== CLEARING CACHES ===" -ForegroundColor Yellow

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear && php artisan queue:restart"

Write-Host "=== DONE ===" -ForegroundColor Green
