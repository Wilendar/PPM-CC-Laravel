# Check ProductForm layout classes
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== CHECKING PRODUCTFORM LAYOUT ===" -ForegroundColor Cyan

# 1. Check for category-form-main-container class
Write-Host "`n[1] Searching for category-form-main-container:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -n 'category-form-main-container' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php || echo 'CLASS NOT FOUND'"

# 2. Check for category-form-right-column class
Write-Host "`n[2] Searching for category-form-right-column:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -n 'category-form-right-column' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php || echo 'CLASS NOT FOUND'"

# 3. Check for "Szybkie akcje" section
Write-Host "`n[3] Finding Szybkie akcje section (with context):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -B 5 -A 2 'Szybkie akcje' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php | head -10"

# 4. Check if modal exists for shop selector
Write-Host "`n[4] Checking for showShopSelector modal:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -n 'showShopSelector' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php | head -5"

Write-Host "`n=== CHECK COMPLETE ===" -ForegroundColor Green