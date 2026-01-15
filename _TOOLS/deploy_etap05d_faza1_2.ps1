# ETAP_05d FAZA 1-2 Deployment Script
# Per-shop Vehicle Compatibility System
# 2025-12-05

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== ETAP_05d FAZA 1-2 DEPLOYMENT ===" -ForegroundColor Cyan
Write-Host "Per-shop Vehicle Compatibility System" -ForegroundColor Gray
Write-Host ""

# Step 1: Deploy Migrations
Write-Host "[1/5] Uploading Migrations..." -ForegroundColor Yellow
$migrations = @(
    "database/migrations/2025_12_05_000001_add_shop_id_to_vehicle_compatibility.php",
    "database/migrations/2025_12_05_000002_add_brand_restrictions_to_prestashop_shops.php",
    "database/migrations/2025_12_05_000003_create_compatibility_suggestions_table.php",
    "database/migrations/2025_12_05_000004_create_compatibility_bulk_operations_table.php"
)

foreach ($migration in $migrations) {
    $fileName = Split-Path $migration -Leaf
    Write-Host "  - $fileName" -ForegroundColor Gray
    pscp -i $HostidoKey -P 64321 $migration "$RemoteBase/$migration"
}

# Step 2: Deploy Updated Models
Write-Host "[2/5] Uploading Updated Models..." -ForegroundColor Yellow
$updatedModels = @(
    "app/Models/VehicleCompatibility.php",
    "app/Models/PrestaShopShop.php"
)

foreach ($model in $updatedModels) {
    $fileName = Split-Path $model -Leaf
    Write-Host "  - $fileName" -ForegroundColor Gray
    pscp -i $HostidoKey -P 64321 $model "$RemoteBase/$model"
}

# Step 3: Deploy New Models
Write-Host "[3/5] Uploading New Models..." -ForegroundColor Yellow
$newModels = @(
    "app/Models/CompatibilitySuggestion.php",
    "app/Models/CompatibilityBulkOperation.php"
)

foreach ($model in $newModels) {
    $fileName = Split-Path $model -Leaf
    Write-Host "  - $fileName" -ForegroundColor Gray
    pscp -i $HostidoKey -P 64321 $model "$RemoteBase/$model"
}

# Step 4: Deploy Services (create directory first)
Write-Host "[4/5] Uploading Services..." -ForegroundColor Yellow

# Create Compatibility directory if not exists
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mkdir -p domains/ppm.mpptrade.pl/public_html/app/Services/Compatibility"

$services = @(
    "app/Services/Compatibility/SmartSuggestionEngine.php",
    "app/Services/Compatibility/ShopFilteringService.php"
)

foreach ($service in $services) {
    $fileName = Split-Path $service -Leaf
    Write-Host "  - $fileName" -ForegroundColor Gray
    pscp -i $HostidoKey -P 64321 $service "$RemoteBase/$service"
}

# Step 5: Run Migrations and Clear Cache
Write-Host "[5/5] Running Migrations and Clearing Cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force && php artisan cache:clear && php artisan config:clear && php artisan view:clear"

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host ""
Write-Host "Created files:" -ForegroundColor Cyan
Write-Host "  - 4 Migrations (vehicle_compatibility, prestashop_shops, suggestions, bulk_operations)"
Write-Host "  - 2 Updated Models (VehicleCompatibility, PrestaShopShop)"
Write-Host "  - 2 New Models (CompatibilitySuggestion, CompatibilityBulkOperation)"
Write-Host "  - 2 New Services (SmartSuggestionEngine, ShopFilteringService)"
