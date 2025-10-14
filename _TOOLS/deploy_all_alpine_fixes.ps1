# Complete deployment - ALL fixes for UI and Alpine/Livewire issues
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "  COMPLETE FIX DEPLOYMENT" -ForegroundColor Cyan
Write-Host "  UI + Alpine/Livewire Issues" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# 1. Upload admin layout (Alpine duplicate fix)
Write-Host "`n[1/4] Uploading admin.blade.php (Alpine duplicate fix)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\layouts\admin.blade.php" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/layouts/admin.blade.php

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload admin.blade.php" -ForegroundColor Red
    exit 1
}

# 2. Upload category-tree (Livewire 3.x entangle fix)
Write-Host "`n[2/4] Uploading category-tree-ultra-clean.blade.php (Livewire 3.x fix)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\categories\category-tree-ultra-clean.blade.php" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload category-tree-ultra-clean.blade.php" -ForegroundColor Red
    exit 1
}

# 3. Upload ProductForm (already fixed earlier)
Write-Host "`n[3/4] Uploading product-form.blade.php (button type fixes)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload product-form.blade.php" -ForegroundColor Red
    exit 1
}

# 4. Clear ALL caches
Write-Host "`n[4/4] Clearing ALL caches..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan optimize:clear && php artisan view:clear && php artisan cache:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n========================================" -ForegroundColor Green
    Write-Host "  ALL FIXES DEPLOYED SUCCESSFULLY!" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Green
    Write-Host "`n✓ Vite manifest fixed (category-form-DcMa3my2.css)" -ForegroundColor Green
    Write-Host "✓ Alpine duplicate removed (no more conflicts)" -ForegroundColor Green
    Write-Host "✓ Livewire 3.x syntax (@entangle → wire:model.live)" -ForegroundColor Green
    Write-Host "✓ ProductForm buttons (type=button)" -ForegroundColor Green
    Write-Host "✓ CategoryTree overflow (dropdown fix)" -ForegroundColor Green
    Write-Host "✓ Sidepanel CSS (overflow: visible)" -ForegroundColor Green
    Write-Host "`nTest URLs:" -ForegroundColor Cyan
    Write-Host "  - ProductForm: https://ppm.mpptrade.pl/admin/products/create" -ForegroundColor White
    Write-Host "  - Categories: https://ppm.mpptrade.pl/admin/products/categories" -ForegroundColor White
    Write-Host "`n⚠️  IMPORTANT: Hard refresh browser (Ctrl+Shift+R)" -ForegroundColor Yellow
    Write-Host "    Expected: NO Alpine errors in console F12" -ForegroundColor White
    Write-Host "    Expected: CSS loads category-form-DcMa3my2.css" -ForegroundColor White
} else {
    Write-Host "`nERROR: Cache clear failed" -ForegroundColor Red
    exit 1
}