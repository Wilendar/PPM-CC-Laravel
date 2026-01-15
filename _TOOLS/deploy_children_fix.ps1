#!/usr/bin/env pwsh
# Deploy Category Children Fix 2025-11-27
$ErrorActionPreference = "Stop"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING CATEGORY CHILDREN FIX ===" -ForegroundColor Cyan

Write-Host "[1/5] Uploading ALL assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "$RemoteBase/public/build/assets/"

Write-Host "[2/5] Uploading manifest to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "$RemoteBase/public/build/manifest.json"

Write-Host "[3/5] Uploading category-tree-item.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/management/partials/category-tree-item.blade.php" "$RemoteBase/resources/views/livewire/products/management/partials/category-tree-item.blade.php"

Write-Host "[4/5] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "[5/5] Complete!" -ForegroundColor Green
Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
