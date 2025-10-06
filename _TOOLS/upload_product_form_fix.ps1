# Upload product-form button fix
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== UPLOADING PRODUCT-FORM BUTTON FIX ===" -ForegroundColor Cyan

# 1. Upload product-form.blade.php
Write-Host "`n[1/2] Uploading product-form.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload product-form.blade.php" -ForegroundColor Red
    exit 1
}

# 2. Clear cache
Write-Host "`n[2/2] Clearing Laravel cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n=== SUCCESS ===" -ForegroundColor Green
    Write-Host "✓ Shop management buttons fixed (type=button added)" -ForegroundColor Green
    Write-Host "`nTest URL:" -ForegroundColor Cyan
    Write-Host "  - ProductForm: https://ppm.mpptrade.pl/admin/products/create" -ForegroundColor White
    Write-Host "  - Edit product and test 'Zarzadzanie sklepami' buttons" -ForegroundColor White
} else {
    Write-Host "`nERROR: Cache clear failed" -ForegroundColor Red
    exit 1
}