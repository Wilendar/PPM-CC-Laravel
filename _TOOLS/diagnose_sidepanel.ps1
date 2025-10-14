# Diagnose Sidepanel CSS Issues
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== SIDEPANEL CSS DIAGNOSTICS ===" -ForegroundColor Cyan

# 1. Check if wrapper exists in deployed file
Write-Host "`n[1] Checking for category-form-container wrapper:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -n 'category-form-container' domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php | head -2"

# 2. Check CSS file timestamp
Write-Host "`n[2] CSS file timestamp:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "stat -c '%y' domains/ppm.mpptrade.pl/public_html/public/build/assets/category-form-sVKl11ny.css"

# 3. Check if CSS contains the critical rules
Write-Host "`n[3] Critical CSS rules check:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -o 'category-form-right-column{[^}]*position:sticky' domains/ppm.mpptrade.pl/public_html/public/build/assets/category-form-sVKl11ny.css"

# 4. Check manifest files are synced
Write-Host "`n[4] Manifest files check:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep 'category-form' domains/ppm.mpptrade.pl/public_html/public/build/manifest.json && grep 'category-form' domains/ppm.mpptrade.pl/public_html/public/build/.vite/manifest.json"

# 5. Check admin layout loads CSS
Write-Host "`n[5] Admin layout CSS loading:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -n 'category-form.css' domains/ppm.mpptrade.pl/public_html/resources/views/layouts/admin.blade.php"

Write-Host "`n=== DIAGNOSTICS COMPLETE ===" -ForegroundColor Green
Write-Host "`nRECOMMENDATIONS:" -ForegroundColor Cyan
Write-Host "1. Hard refresh browser (Ctrl+Shift+R)"
Write-Host "2. Check F12 > Network > category-form-sVKl11ny.css loads successfully"
Write-Host "3. Check F12 > Elements > .category-form-right-column has position:sticky"
Write-Host "4. Remove Tailwind CDN from production (cdn.tailwindcss.com warning)"