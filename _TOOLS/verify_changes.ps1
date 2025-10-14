# Verify our changes are actually in the files on server
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== VERIFYING CHANGES ON SERVER ===" -ForegroundColor Cyan

# 1. Show exact line with "Dodaj do sklepu" button
Write-Host "`n[1] Checking 'Dodaj do sklepu' button (should have type=button):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -A 2 -B 2 'Dodaj do sklepu' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php"

# 2. Check overflow-y in category-tree
Write-Host "`n[2] Checking overflow-y in category-tree:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -B 1 -A 1 'overflow-y: visible' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php"

# 3. Check if CSS has overflow: visible
Write-Host "`n[3] Checking CSS for overflow fixes (first 500 chars):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "head -c 500 domains/ppm.mpptrade.pl/public_html/public/build/assets/category-form-DcMa3my2.css"

Write-Host "`n[4] Checking if Szybkie akcje section exists:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -c 'Szybkie akcje' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php"

Write-Host "`n=== VERIFICATION COMPLETE ===" -ForegroundColor Green