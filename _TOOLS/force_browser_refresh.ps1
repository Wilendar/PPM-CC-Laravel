# Force browser to reload by adding cache buster
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== FORCE BROWSER REFRESH STRATEGY ===" -ForegroundColor Cyan

# 1. Touch manifest.json to update timestamp
Write-Host "`n[1] Updating manifest.json timestamp..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "touch domains/ppm.mpptrade.pl/public_html/public/build/.vite/manifest.json"

# 2. Touch admin.blade.php
Write-Host "`n[2] Updating admin.blade.php timestamp..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "touch domains/ppm.mpptrade.pl/public_html/resources/views/layouts/admin.blade.php"

# 3. Touch category-tree
Write-Host "`n[3] Updating category-tree timestamp..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "touch domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php"

# 4. Remove ALL storage/framework files
Write-Host "`n[4] Removing ALL framework cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && rm -rf storage/framework/cache/* storage/framework/views/* storage/framework/sessions/* bootstrap/cache/*"

# 5. Restart PHP-FPM if possible
Write-Host "`n[5] Attempting to restart PHP-FPM..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "killall -USR2 php-fpm 2>/dev/null || echo 'Cannot restart PHP-FPM (no permissions)'"

# 6. Final cache clear
Write-Host "`n[6] Final Laravel cache clear..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan optimize:clear"

Write-Host "`n=== FORCE REFRESH COMPLETE ===" -ForegroundColor Green
Write-Host "`n⚠️  CRITICAL: User MUST do the following:" -ForegroundColor Yellow
Write-Host "  1. Close ALL browser tabs with ppm.mpptrade.pl" -ForegroundColor White
Write-Host "  2. Clear browser cache (Ctrl+Shift+Delete)" -ForegroundColor White
Write-Host "  3. Open in NEW incognito window" -ForegroundColor White
Write-Host "  4. If it works in incognito -> clear normal browser cache again" -ForegroundColor White