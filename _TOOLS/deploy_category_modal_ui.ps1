# Deploy Category Preview Modal UI Changes
# ETAP_07f: Comparison Tree Node ProductForm Style

$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING CATEGORY PREVIEW MODAL UI CHANGES ===" -ForegroundColor Cyan

# Step 1: Upload comparison-tree-node.blade.php
Write-Host "[1/4] Uploading comparison-tree-node.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/components/comparison-tree-node.blade.php" "${RemoteBase}/resources/views/components/comparison-tree-node.blade.php"

# Step 2: Upload Vite assets
Write-Host "[2/4] Uploading Vite assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "public/build/assets/*" "${RemoteBase}/public/build/assets/"

# Step 3: Upload manifest
Write-Host "[3/4] Uploading manifest.json..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "public/build/.vite/manifest.json" "${RemoteBase}/public/build/manifest.json"

# Step 4: Clear cache
Write-Host "[4/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
