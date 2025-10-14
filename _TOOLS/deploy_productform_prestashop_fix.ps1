# Deploy ProductForm PrestaShop Lazy Loading Fix
# ETAP_07 FIX - Wczytywanie danych produktow z PrestaShop

$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "DEPLOY: ProductForm PrestaShop Fix" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# 1. Upload ProductForm.php
Write-Host "[1/3] Uploading ProductForm.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\Management\ProductForm.php" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] ProductForm.php uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Failed to upload ProductForm.php" -ForegroundColor Red
    exit 1
}

# 2. Upload product-form.blade.php
Write-Host "`n[2/3] Uploading product-form.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] product-form.blade.php uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Failed to upload product-form.blade.php" -ForegroundColor Red
    exit 1
}

# 3. Clear caches
Write-Host "`n[3/3] Clearing caches..." -ForegroundColor Yellow
$cacheCommands = @(
    "cd domains/ppm.mpptrade.pl/public_html",
    "php artisan view:clear",
    "php artisan cache:clear"
) -join " && "

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch $cacheCommands

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Caches cleared" -ForegroundColor Green
} else {
    Write-Host "[WARNING] Cache clear may have failed" -ForegroundColor Yellow
}

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "DEPLOYMENT COMPLETED SUCCESSFULLY" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Green

Write-Host "TESTING INSTRUCTIONS:" -ForegroundColor Cyan
Write-Host "1. Otworz produkt w trybie edycji: https://ppm.mpptrade.pl/admin/products/edit/{id}" -ForegroundColor White
Write-Host "2. Kliknij w label sklepu PrestaShop (np. 'dev.mpptrade.pl')" -ForegroundColor White
Write-Host "3. Sprawdz czy dane sie wczytaly automatycznie" -ForegroundColor White
Write-Host "4. Kliknij przycisk 'Wczytaj z PrestaShop' (reload danych)" -ForegroundColor White
Write-Host "5. Kliknij link '🔗 PrestaShop' - powinien otworzyc frontend produktu" -ForegroundColor White
Write-Host "`nExpected URL format: https://shop.com/{id}-{slug}.html" -ForegroundColor Gray
Write-Host "NOT: https://shop.com//admin-dev/index.php?controller=AdminProducts&id_product={id}`n" -ForegroundColor Gray
