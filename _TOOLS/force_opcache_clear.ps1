$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "Forcing OPcache and Laravel cache clear..." -ForegroundColor Yellow

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan view:clear && php artisan config:clear && php artisan route:clear && php artisan clear-compiled && php -r 'opcache_reset();'
"@

Write-Host "Cache cleared. Restarting PHP-FPM..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "killall -9 php-cgi"

Write-Host "Done. Wait 5 seconds for PHP to restart..." -ForegroundColor Green
Start-Sleep -Seconds 5
