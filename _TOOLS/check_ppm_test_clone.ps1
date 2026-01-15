# Check PPM-TEST-clone1 product and its media
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== FINDING PRODUCT PPM-TEST-clone1 ===" -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u host379076_ppm -pqkS4FuXMMDDN4DJhatg6 host379076_ppm -e ""SELECT id, sku, name, created_at FROM products WHERE sku LIKE '%PPM-TEST-clone%' ORDER BY created_at DESC LIMIT 5;"""

Write-Host ""
Write-Host "=== CHECKING MEDIA FOR THIS PRODUCT ===" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u host379076_ppm -pqkS4FuXMMDDN4DJhatg6 host379076_ppm -e ""SELECT m.id, m.product_id, m.filename, m.disk, m.is_primary, m.created_at FROM media m JOIN products p ON m.product_id = p.id WHERE p.sku LIKE '%PPM-TEST-clone%';"""

Write-Host ""
Write-Host "=== CHECKING PRODUCT_SHOP_DATA ===" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u host379076_ppm -pqkS4FuXMMDDN4DJhatg6 host379076_ppm -e ""SELECT psd.id, psd.product_id, psd.shop_id, psd.prestashop_id, psd.sync_status FROM product_shop_data psd JOIN products p ON psd.product_id = p.id WHERE p.sku LIKE '%PPM-TEST-clone%';"""

Write-Host ""
Write-Host "=== CHECKING IMPORT/SYNC LOGS ===" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -200 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -i 'PPM-TEST-clone'"
