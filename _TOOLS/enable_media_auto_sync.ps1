$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
Write-Host "Enabling media.auto_sync_on_product_sync..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=""App\Models\SystemSetting::set('media.auto_sync_on_product_sync', true); echo 'Setting enabled: ' . (App\Models\SystemSetting::get('media.auto_sync_on_product_sync') ? 'TRUE' : 'FALSE');"""
Write-Host "Done!" -ForegroundColor Green
