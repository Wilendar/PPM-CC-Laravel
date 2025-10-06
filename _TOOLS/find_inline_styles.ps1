# Find inline styles that might override CSS
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== FINDING INLINE STYLES ===" -ForegroundColor Cyan

# 1. Search for style=" in ProductForm
Write-Host "`n[1] Inline styles in ProductForm:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -n 'style=\""' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php | head -10 || echo 'No inline styles found'"

# 2. Search for style=" in CategoryTree
Write-Host "`n[2] Inline styles in CategoryTree:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -n 'style=\""' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php | head -10 || echo 'No inline styles found'"

# 3. Check for z-index or overflow in inline styles
Write-Host "`n[3] Looking for overflow/z-index in inline styles:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -n 'style=\"".*overflow\|style=\"".*z-index' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php || echo 'Not found'"

Write-Host "`n=== SEARCH COMPLETE ===" -ForegroundColor Green