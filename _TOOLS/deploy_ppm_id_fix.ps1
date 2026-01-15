#!/usr/bin/env pwsh
$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.nl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING PPM ID FIX (2025-11-27) ===" -ForegroundColor Cyan

Write-Host "[1/5] Uploading ALL Vite assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\assets\*" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/assets/"

Write-Host "[2/5] Uploading manifest to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\public\build\.vite\manifest.json" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/public/build/manifest.json"

Write-Host "[3/5] Uploading ProductForm.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\Management\ProductForm.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php"

Write-Host "[4/5] Uploading product-form.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php"

Write-Host "[5/5] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
