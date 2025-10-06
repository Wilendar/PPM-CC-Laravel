# Deep diagnostics - why changes don't appear
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== DEEP DIAGNOSTICS ===" -ForegroundColor Cyan

# 1. Check Laravel error logs
Write-Host "`n[1] Last 20 lines of Laravel log:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "tail -20 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log 2>/dev/null || echo 'No errors logged'"

# 2. Check which component is actually being used
Write-Host "`n[2] Checking ProductForm component class:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -A 3 'class ProductForm' domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php | head -5"

# 3. Check route to products/create
Write-Host "`n[3] Checking routes for products:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan route:list | grep 'products/create'"

# 4. Check .htaccess or nginx config issues
Write-Host "`n[4] Checking .htaccess for cache headers:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "grep -i 'cache' domains/ppm.mpptrade.pl/public_html/.htaccess 2>/dev/null || echo 'No cache directives'"

# 5. Force clear ALL caches
Write-Host "`n[5] Force clearing ALL caches..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan optimize:clear && php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan route:clear"

# 6. Check Livewire manifest
Write-Host "`n[6] Checking Livewire manifest:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "ls -lh domains/ppm.mpptrade.pl/public_html/bootstrap/cache/livewire-components.php 2>/dev/null || echo 'Manifest not found'"

Write-Host "`n=== DIAGNOSTICS COMPLETE ===" -ForegroundColor Green
Write-Host "`nNEXT STEPS:" -ForegroundColor Yellow
Write-Host "1. Hard refresh browser (Ctrl+Shift+R)" -ForegroundColor White
Write-Host "2. Clear browser cache completely" -ForegroundColor White
Write-Host "3. Try in incognito mode" -ForegroundColor White
Write-Host "4. Check browser console for JavaScript errors (F12)" -ForegroundColor White