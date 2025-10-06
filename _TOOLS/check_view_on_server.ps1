# Check what's actually in the view on server
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== CHECKING VIEW ON SERVER ===" -ForegroundColor Cyan

# Check line 19 of category-tree-ultra-clean
Write-Host "`n[1] Lines 15-25 of category-tree-ultra-clean.blade.php:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "sed -n '15,25p' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php"

# Search for any hardcoded CSS links
Write-Host "`n[2] Searching for hardcoded CSS links in category-tree:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -n 'href.*css\|link.*stylesheet' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php || echo 'No hardcoded CSS links found'"

# Check for any <style> or <link> tags
Write-Host "`n[3] Checking for style/link tags:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -n '<style\|<link' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php | head -10 || echo 'No style/link tags found'"

Write-Host "`n=== CHECK COMPLETE ===" -ForegroundColor Green