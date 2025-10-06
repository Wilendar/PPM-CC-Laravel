# Deep cache clear - nuclear option
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== DEEP CACHE CLEAR - NUCLEAR OPTION ===" -ForegroundColor Cyan

# 1. Check what manifest shows now
Write-Host "`n[1] Current manifest on server:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cat domains/ppm.mpptrade.pl/public_html/public/build/.vite/manifest.json | grep category-form"

# 2. Remove ALL compiled views
Write-Host "`n[2] Removing ALL compiled views..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "rm -rf domains/ppm.mpptrade.pl/public_html/storage/framework/views/*"

# 3. Remove bootstrap cache
Write-Host "`n[3] Removing bootstrap cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "rm -rf domains/ppm.mpptrade.pl/public_html/bootstrap/cache/*"

# 4. Clear all Laravel caches
Write-Host "`n[4] Clearing ALL Laravel caches..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan optimize:clear && php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan route:clear"

# 5. Verify CSS files exist
Write-Host "`n[5] Listing CSS files in build/assets:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "ls -lh domains/ppm.mpptrade.pl/public_html/public/build/assets/category-form-*.css"

# 6. Check if old CSS file still exists (should NOT)
Write-Host "`n[6] Checking if old CSS exists (should be 'No such file'):" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "ls domains/ppm.mpptrade.pl/public_html/public/build/assets/category-form-B7Z5OGhL.css 2>&1"

Write-Host "`n=== DEEP CACHE CLEAR COMPLETE ===" -ForegroundColor Green
Write-Host "`nNow try hard refresh (Ctrl+Shift+R)" -ForegroundColor Yellow