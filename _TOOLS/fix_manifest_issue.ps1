# Fix Vite manifest issue - upload complete build folder
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== FIXING VITE MANIFEST ISSUE ===" -ForegroundColor Cyan

# 1. Check what manifest is on server
Write-Host "`n[1] Checking server manifest:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cat domains/ppm.mpptrade.pl/public_html/public/build/.vite/manifest.json | grep category-form"

# 2. Upload NEW manifest.json
Write-Host "`n[2] Uploading new manifest.json..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\.vite\manifest.json" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/.vite/manifest.json

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload manifest.json" -ForegroundColor Red
    exit 1
}

# 3. Remove old CSS files to avoid confusion
Write-Host "`n[3] Removing old CSS files..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "rm -f domains/ppm.mpptrade.pl/public_html/public/build/assets/category-form-B7Z5OGhL.css"

# 4. Verify new manifest
Write-Host "`n[4] Verifying new manifest on server:" -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cat domains/ppm.mpptrade.pl/public_html/public/build/.vite/manifest.json | grep category-form"

# 5. Clear all caches
Write-Host "`n[5] Clearing all caches..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan optimize:clear"

Write-Host "`n=== MANIFEST FIX COMPLETE ===" -ForegroundColor Green
Write-Host "`nNow the site should load: category-form-DcMa3my2.css" -ForegroundColor White
Write-Host "`nPlease hard refresh (Ctrl+Shift+R) and test again" -ForegroundColor Yellow