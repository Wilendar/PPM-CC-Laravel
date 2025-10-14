# Complete deployment of all UI fixes
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  COMPLETE UI FIXES DEPLOYMENT" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# 1. Upload ProductForm with button fixes
Write-Host "`n[1/4] Uploading product-form.blade.php (shop buttons fix)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload product-form.blade.php" -ForegroundColor Red
    exit 1
}

# 2. Upload category-tree with overflow fix
Write-Host "`n[2/4] Uploading category-tree-ultra-clean.blade.php (dropdown fix)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\categories\category-tree-ultra-clean.blade.php" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload category-tree-ultra-clean.blade.php" -ForegroundColor Red
    exit 1
}

# 3. Upload updated CSS with sidepanel fixes
Write-Host "`n[3/4] Uploading category-form CSS (sidepanel fix)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\assets\category-form-DcMa3my2.css" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/assets/

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload CSS" -ForegroundColor Red
    exit 1
}

# 4. Clear all caches
Write-Host "`n[4/4] Clearing all Laravel caches..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n========================================" -ForegroundColor Green
    Write-Host "  ALL FIXES DEPLOYED SUCCESSFULLY!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "`n✓ ProductForm shop buttons (type=button)" -ForegroundColor Green
    Write-Host "✓ CategoryTree dropdown (overflow-y: visible)" -ForegroundColor Green
    Write-Host "✓ Sidepanel CSS (overflow: visible)" -ForegroundColor Green
    Write-Host "`nTest URLs:" -ForegroundColor Cyan
    Write-Host "  - ProductForm: https://ppm.mpptrade.pl/admin/products/create" -ForegroundColor White
    Write-Host "  - Categories: https://ppm.mpptrade.pl/admin/products/categories" -ForegroundColor White
    Write-Host "`nPlease test:" -ForegroundColor Yellow
    Write-Host "  1. 'Dodaj do sklepu' button - should open modal" -ForegroundColor White
    Write-Host "  2. 'Szybkie akcje' sidepanel - should be on right side" -ForegroundColor White
    Write-Host "  3. Category dropdown (...) - should not be cut off" -ForegroundColor White
} else {
    Write-Host "`nERROR: Cache clear failed" -ForegroundColor Red
    exit 1
}