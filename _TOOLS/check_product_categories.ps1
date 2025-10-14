# Check product_categories data for debugging per-shop categories issue
# Product ID: 10957

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostName = "host379076@host379076.hostido.net.pl"
$Port = 64321

Write-Host "Checking product_categories for product 10957..." -ForegroundColor Cyan

$query = "SELECT id, product_id, category_id, shop_id, is_primary, sort_order FROM product_categories WHERE product_id = 10957 ORDER BY shop_id, sort_order"

$command = "cd domains/ppm.mpptrade.pl/public_html && php artisan tinker --execute=`"echo json_encode(DB::select('$query'), JSON_PRETTY_PRINT);`""

plink -ssh $HostName -P $Port -i $HostidoKey -batch $command
