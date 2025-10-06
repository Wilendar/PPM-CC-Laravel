# Find which view is used for products/edit
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== FINDING EDIT VIEW ===" -ForegroundColor Cyan

# 1. Get route details for products edit
Write-Host "`n[1] Route details for admin/products/*/edit:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan route:list | grep 'products.*edit'"

# 2. Search for views in pages/products
Write-Host "`n[2] Looking for products views in pages:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "find domains/ppm.mpptrade.pl/public_html/resources/views/pages/products -name '*.blade.php' 2>/dev/null || echo 'No pages/products views'"

# 3. Check for hardcoded CSS in ALL product views
Write-Host "`n[3] Searching for B7Z5OGhL in ALL views:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -r 'B7Z5OGhL' domains/ppm.mpptrade.pl/public_html/resources/views/ 2>/dev/null || echo 'Not found in views'"

# 4. Check for hardcoded CSS in public/build folder references
Write-Host "`n[4] Checking for hardcoded references in layouts:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -r 'category-form' domains/ppm.mpptrade.pl/public_html/resources/views/layouts/ 2>/dev/null || echo 'Not found in layouts'"

Write-Host "`n=== SEARCH COMPLETE ===" -ForegroundColor Green