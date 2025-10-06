# Check what's actually on the production server
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== CHECKING PRODUCTION SERVER FILES ===" -ForegroundColor Cyan

# 1. Check ProductForm timestamp
Write-Host "`n[1] ProductForm file timestamp:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "ls -lh domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php"

# 2. Check if type=button exists in ProductForm
Write-Host "`n[2] Checking for 'type=button' in ProductForm:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -c 'type=\""button\""' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php"

# 3. Check category-tree timestamp
Write-Host "`n[3] CategoryTree file timestamp:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "ls -lh domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php"

# 4. Check for overflow-y: visible in category-tree
Write-Host "`n[4] Checking for 'overflow-y: visible' in CategoryTree:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -c 'overflow-y: visible' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php"

# 5. Check CSS timestamp
Write-Host "`n[5] CSS file timestamp:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "ls -lh domains/ppm.mpptrade.pl/public_html/public/build/assets/category-form-DcMa3my2.css"

# 6. Check PHP opcache status
Write-Host "`n[6] PHP opcache status:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php -r 'echo opcache_get_status() ? \"\"ENABLED\"\" : \"\"DISABLED\"\";'"

# 7. Clear opcache
Write-Host "`n[7] Clearing PHP opcache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan optimize:clear"

Write-Host "`n=== DIAGNOSTICS COMPLETE ===" -ForegroundColor Green