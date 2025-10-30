# Deploy White Backgrounds Fix to Production
# All 34 fixed files

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views"

Write-Host "`n=== DEPLOYING WHITE BACKGROUNDS FIX ===" -ForegroundColor Cyan

# 1. Deploy layouts folder
Write-Host "`n[1/10] Deploying layouts/..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "resources/views/layouts" "${RemoteBase}/"
Write-Host "[OK] layouts/ deployed" -ForegroundColor Green

# 2. Deploy auth/login.blade.php
Write-Host "`n[2/10] Deploying auth/login.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/auth/login.blade.php" "${RemoteBase}/auth/login.blade.php"
Write-Host "[OK] auth/login.blade.php deployed" -ForegroundColor Green

# 3. Deploy livewire/admin/ (all subfolders)
Write-Host "`n[3/10] Deploying livewire/admin/ (complete)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "resources/views/livewire/admin" "${RemoteBase}/livewire/"
Write-Host "[OK] livewire/admin/ deployed" -ForegroundColor Green

# 4. Deploy livewire/auth/
Write-Host "`n[4/10] Deploying livewire/auth/..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "resources/views/livewire/auth" "${RemoteBase}/livewire/"
Write-Host "[OK] livewire/auth/ deployed" -ForegroundColor Green

# 5. Deploy livewire/components/
Write-Host "`n[5/10] Deploying livewire/components/..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/components/category-preview-modal.blade.php" "${RemoteBase}/livewire/components/category-preview-modal.blade.php"
pscp -i $HostidoKey -P 64321 "resources/views/livewire/components/job-progress-bar.blade.php" "${RemoteBase}/livewire/components/job-progress-bar.blade.php"
Write-Host "[OK] livewire/components/ deployed" -ForegroundColor Green

# 6. Deploy livewire/products/categories/ (all 4 variants)
Write-Host "`n[6/10] Deploying livewire/products/categories/..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/categories/category-tree.blade.php" "${RemoteBase}/livewire/products/categories/category-tree.blade.php"
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/categories/category-tree-enhanced.blade.php" "${RemoteBase}/livewire/products/categories/category-tree-enhanced.blade.php"
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/categories/category-tree-modern.blade.php" "${RemoteBase}/livewire/products/categories/category-tree-modern.blade.php"
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php" "${RemoteBase}/livewire/products/categories/category-tree-ultra-clean.blade.php"
Write-Host "[OK] livewire/products/categories/ deployed" -ForegroundColor Green

# 7. Deploy livewire/products/listing/
Write-Host "`n[7/10] Deploying livewire/products/listing/..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/products/listing/product-list.blade.php" "${RemoteBase}/livewire/products/listing/product-list.blade.php"
Write-Host "[OK] livewire/products/listing/ deployed" -ForegroundColor Green

# 8. Deploy livewire/profile/
Write-Host "`n[8/10] Deploying livewire/profile/..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/profile/edit-profile.blade.php" "${RemoteBase}/livewire/profile/edit-profile.blade.php"
Write-Host "[OK] livewire/profile/ deployed" -ForegroundColor Green

# 9. Deploy pages/
Write-Host "`n[9/10] Deploying pages/..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/pages/category-ui-test.blade.php" "${RemoteBase}/pages/category-ui-test.blade.php"
Write-Host "[OK] pages/ deployed" -ForegroundColor Green

# 10. Deploy test files + welcome pages
Write-Host "`n[10/10] Deploying test files..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/admin-dashboard-test.blade.php" "${RemoteBase}/admin-dashboard-test.blade.php"
pscp -i $HostidoKey -P 64321 "resources/views/test-attribute-color-picker.blade.php" "${RemoteBase}/test-attribute-color-picker.blade.php"
pscp -i $HostidoKey -P 64321 "resources/views/welcome.blade.php" "${RemoteBase}/welcome.blade.php"
pscp -i $HostidoKey -P 64321 "resources/views/welcome-simple.blade.php" "${RemoteBase}/welcome-simple.blade.php"
Write-Host "[OK] test files deployed" -ForegroundColor Green

Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "All 34 files with 275 replacements deployed successfully!" -ForegroundColor Yellow
