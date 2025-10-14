# Upload category dropdown and sidepanel fixes
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== UPLOADING CATEGORY FIXES ===" -ForegroundColor Cyan

# 1. Upload category-actions dropdown fix
Write-Host "`n[1/3] Uploading category-actions.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\categories\partials\category-actions.blade.php" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/partials/category-actions.blade.php

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload category-actions.blade.php" -ForegroundColor Red
    exit 1
}

# 2. Upload built CSS
Write-Host "`n[2/3] Uploading category-form CSS..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\assets\category-form-*.css" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/assets/

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload CSS" -ForegroundColor Red
    exit 1
}

# 3. Clear cache
Write-Host "`n[3/3] Clearing Laravel cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n=== SUCCESS ===" -ForegroundColor Green
    Write-Host "✓ Dropdown auto-positioning fixed" -ForegroundColor Green
    Write-Host "✓ Sidepanel overflow fixed" -ForegroundColor Green
    Write-Host "`nTest URLs:" -ForegroundColor Cyan
    Write-Host "  - Categories: https://ppm.mpptrade.pl/admin/products/categories" -ForegroundColor White
    Write-Host "  - ProductForm: https://ppm.mpptrade.pl/admin/products/create" -ForegroundColor White
} else {
    Write-Host "`nERROR: Cache clear failed" -ForegroundColor Red
    exit 1
}