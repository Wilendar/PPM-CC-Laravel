$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== SPRAWDZAM BAZĘ DANYCH ===" -ForegroundColor Cyan
Write-Host ""

# Simple query without complex PHP
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && mysql -u host379076_ppm -p'g4PfqXPLJzKg4PfqXPLJz' host379076_ppm -e 'SELECT product_id, shop_id, LEFT(category_mappings, 200) as mappings_preview FROM product_shop_data WHERE product_id = 11034 AND shop_id = 1 LIMIT 1;'"

Write-Host "`n=== DONE ===" -ForegroundColor Green
