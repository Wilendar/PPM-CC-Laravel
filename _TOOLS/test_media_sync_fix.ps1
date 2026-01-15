# Test media sync fix for product 11105
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING PRODUCT PRESTASHOP DATA ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u host379076_ppm -pqkS4FuXMMDDN4DJhatg6 host379076_ppm -e 'SELECT psd.id, psd.product_id, psd.shop_id, psd.prestashop_id FROM product_shop_data psd WHERE psd.product_id = 11105;'"

Write-Host ""
Write-Host "=== TRYING MANUAL MEDIA SYNC TRIGGER ===" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute='App\Jobs\Media\SyncMediaFromPrestaShop::dispatch(11105, 1, 1);'"

Write-Host ""
Write-Host "=== CHECKING FOR ERRORS IN LOGS ===" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -30 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -E '(MEDIA SYNC|ERROR|job_id|Duplicate)'"

Write-Host ""
Write-Host "=== CHECKING MEDIA TABLE FOR PRODUCT ===" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u host379076_ppm -pqkS4FuXMMDDN4DJhatg6 host379076_ppm -e 'SELECT id, mediable_id, filename, is_primary, sync_status FROM media WHERE mediable_id = 11105 LIMIT 10;'"
