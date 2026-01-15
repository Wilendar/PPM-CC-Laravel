#!/usr/bin/env pwsh
# Deploy PHP Fix 2025-11-27 - Category Deletion
$ErrorActionPreference = "Stop"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

# Check for --logs flag
if ($args -contains "--logs") {
    Write-Host "=== FETCHING DEBUG LOGS ===" -ForegroundColor Cyan
    plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "tail -50 domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log | grep -iE 'CATEGORY SYNC' -A 2"
    exit
}

Write-Host "=== DEPLOYING CATEGORY DELETION FIX ===" -ForegroundColor Cyan

Write-Host "[1/3] Uploading ProductForm.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Products/Management/ProductForm.php" "$RemoteBase/app/Http/Livewire/Products/Management/ProductForm.php"

Write-Host "[2/3] Uploading CategoryAssociationService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/PrestaShop/CategoryAssociationService.php" "$RemoteBase/app/Services/PrestaShop/CategoryAssociationService.php"

Write-Host "[3/3] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
