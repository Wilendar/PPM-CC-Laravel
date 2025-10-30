# Deploy Category Table Header Fix
# Removes white backgrounds from all category tree table headers

$ErrorActionPreference = "Stop"

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories"

Write-Host "`n=== DEPLOYING CATEGORY THEAD FIX ===" -ForegroundColor Cyan

# Deploy all 5 category tree files
Write-Host "`nDeploying category-tree.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/categories/category-tree.blade.php" "${RemoteBase}/category-tree.blade.php"

Write-Host "Deploying category-tree-compact.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/categories/category-tree-compact.blade.php" "${RemoteBase}/category-tree-compact.blade.php"

Write-Host "Deploying category-tree-enhanced.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/categories/category-tree-enhanced.blade.php" "${RemoteBase}/category-tree-enhanced.blade.php"

Write-Host "Deploying category-tree-modern.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/categories/category-tree-modern.blade.php" "${RemoteBase}/category-tree-modern.blade.php"

Write-Host "Deploying category-tree-ultra-clean.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php" "${RemoteBase}/category-tree-ultra-clean.blade.php"

Write-Host "`n[OK] All 5 files deployed successfully!" -ForegroundColor Green
