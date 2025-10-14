# Verify actual deployed content
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== VERIFYING DEPLOYED CONTENT ===" -ForegroundColor Cyan

# 1. Check if CSS has overflow:visible
Write-Host "`n[1] Checking CSS for overflow rules (first 600 chars):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "head -c 600 domains/ppm.mpptrade.pl/public_html/public/build/assets/category-form-DcMa3my2.css | grep -o 'overflow:visible\|overflow-y:visible' || echo 'OVERFLOW NOT FOUND IN CSS'"

# 2. Check full CSS for category-form-main-container
Write-Host "`n[2] Searching for category-form-main-container in CSS:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -o 'category-form-main-container{[^}]*}' domains/ppm.mpptrade.pl/public_html/public/build/assets/category-form-DcMa3my2.css | head -c 300"

# 3. Check product-form for type=button on "Dodaj do sklepu"
Write-Host "`n[3] Checking 'Dodaj do sklepu' button:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -A 3 'Dodaj do sklepu' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php | head -5"

# 4. Check category-tree for overflow-y: visible
Write-Host "`n[4] Checking category-tree for overflow-y:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep 'overflow-y: visible' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php || echo 'overflow-y NOT FOUND'"

Write-Host "`n=== VERIFICATION COMPLETE ===" -ForegroundColor Green