$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== CHECKING saveAndClose LOGS ===" -ForegroundColor Cyan

# Check current category_mappings in database
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mysql -u host379076_ppm -p'qkS4FuXMMDDN4DJhatg6' host379076_ppm -e 'SELECT category_mappings, sync_status, updated_at FROM product_shop_data WHERE product_id=11034 AND shop_id=1'"

Write-Host "`n=== DONE ===" -ForegroundColor Green
