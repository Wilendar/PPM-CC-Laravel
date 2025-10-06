# Check actual routes on server
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== CHECKING ROUTES ON SERVER ===" -ForegroundColor Cyan

# Get detailed route info for products/create
Write-Host "`n[1] Route details for admin/products/create:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan route:list --name=admin.products.create --verbose"

# Check what's in web.php for admin products
Write-Host "`n[2] Admin products routes in web.php:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -n 'admin.*products' domains/ppm.mpptrade.pl/public_html/routes/web.php | head -20"

Write-Host "`n=== ROUTE CHECK COMPLETE ===" -ForegroundColor Green