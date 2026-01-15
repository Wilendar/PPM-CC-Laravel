# Deploy Product Parameters Panel (Manufacturers, Warehouses, Attributes, Types)
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING PRODUCT PARAMETERS PANEL ===" -ForegroundColor Cyan

# 1. Migration
Write-Host "[1/8] Uploading migration..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "database/migrations/2025_12_10_200000_create_manufacturers_tables.php" "$RemoteBase/database/migrations/2025_12_10_200000_create_manufacturers_tables.php"

# 2. Model
Write-Host "[2/8] Uploading Manufacturer model..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Models/Manufacturer.php" "$RemoteBase/app/Models/Manufacturer.php"

# 3. Livewire Components
Write-Host "[3/8] Uploading Livewire components..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mkdir -p domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Admin/Parameters"
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Admin/Parameters/ProductParametersManager.php" "$RemoteBase/app/Http/Livewire/Admin/Parameters/ProductParametersManager.php"
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Admin/Parameters/ManufacturerManager.php" "$RemoteBase/app/Http/Livewire/Admin/Parameters/ManufacturerManager.php"
pscp -i $HostidoKey -P 64321 "app/Http/Livewire/Admin/Parameters/WarehouseManager.php" "$RemoteBase/app/Http/Livewire/Admin/Parameters/WarehouseManager.php"

# 4. Views
Write-Host "[4/8] Uploading views..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mkdir -p domains/ppm.mpptrade.pl/public_html/resources/views/livewire/admin/parameters"
pscp -i $HostidoKey -P 64321 "resources/views/livewire/admin/parameters/product-parameters-manager.blade.php" "$RemoteBase/resources/views/livewire/admin/parameters/product-parameters-manager.blade.php"
pscp -i $HostidoKey -P 64321 "resources/views/livewire/admin/parameters/manufacturer-manager.blade.php" "$RemoteBase/resources/views/livewire/admin/parameters/manufacturer-manager.blade.php"
pscp -i $HostidoKey -P 64321 "resources/views/livewire/admin/parameters/warehouse-manager.blade.php" "$RemoteBase/resources/views/livewire/admin/parameters/warehouse-manager.blade.php"

# 5. Routes
Write-Host "[5/8] Uploading routes..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "routes/web.php" "$RemoteBase/routes/web.php"

# 6. Sidebar
Write-Host "[6/8] Uploading admin layout (sidebar)..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/layouts/admin.blade.php" "$RemoteBase/resources/views/layouts/admin.blade.php"

# 7. Run migration
Write-Host "[7/8] Running migration..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

# 8. Clear cache
Write-Host "[8/8] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan route:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Test: https://ppm.mpptrade.pl/admin/product-parameters" -ForegroundColor Cyan
