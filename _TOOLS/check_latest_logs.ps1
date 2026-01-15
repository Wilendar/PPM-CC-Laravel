$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Checking media.auto_sync_on_product_sync setting..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=""echo App\Models\SystemSetting::get('media.auto_sync_on_product_sync', 'NOT SET');"""
Write-Host "`n--- Last 50 lines of log (raw) ---" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log"
