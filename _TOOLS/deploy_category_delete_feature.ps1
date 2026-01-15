#!/usr/bin/env pwsh
$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== DEPLOYING CATEGORY DELETE FEATURE ===" -ForegroundColor Cyan

Write-Host "[1/3] Uploading PrestaShop8Client.php (deleteCategory method)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Services\PrestaShop\PrestaShop8Client.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Services/PrestaShop/PrestaShop8Client.php"

Write-Host "[2/3] Uploading ProductForm.php (physical deletion in syncShop)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\Management\ProductForm.php" "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php"

Write-Host "[3/3] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
