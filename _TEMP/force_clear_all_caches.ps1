$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== FORCE CLEARING ALL CACHES ===" -ForegroundColor Cyan

& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear && rm -rf storage/framework/views/* && echo '=== ALL CACHES CLEARED ==='"

Write-Host ""
Write-Host "Done!" -ForegroundColor Green
