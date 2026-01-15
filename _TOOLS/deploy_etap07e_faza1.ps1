$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "=== DEPLOYING ETAP_07e FAZA 1 ===" -ForegroundColor Cyan

# 1. Upload migrations
Write-Host "[1/5] Uploading migrations..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\database\migrations\2025_12_02_100001_create_feature_groups_table.php" "$RemoteBase/database/migrations/2025_12_02_100001_create_feature_groups_table.php"
pscp -i $HostidoKey -P 64321 "$LocalBase\database\migrations\2025_12_02_100002_extend_feature_types_table.php" "$RemoteBase/database/migrations/2025_12_02_100002_extend_feature_types_table.php"
pscp -i $HostidoKey -P 64321 "$LocalBase\database\migrations\2025_12_02_100003_create_prestashop_feature_mappings_table.php" "$RemoteBase/database/migrations/2025_12_02_100003_create_prestashop_feature_mappings_table.php"

# 2. Upload models
Write-Host "[2/5] Uploading models..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Models\FeatureGroup.php" "$RemoteBase/app/Models/FeatureGroup.php"
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Models\FeatureType.php" "$RemoteBase/app/Models/FeatureType.php"
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Models\PrestashopFeatureMapping.php" "$RemoteBase/app/Models/PrestashopFeatureMapping.php"

# 3. Upload seeders
Write-Host "[3/5] Uploading seeders..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\database\seeders\FeatureGroupsSeeder.php" "$RemoteBase/database/seeders/FeatureGroupsSeeder.php"
pscp -i $HostidoKey -P 64321 "$LocalBase\database\seeders\VehicleFeaturesSeeder.php" "$RemoteBase/database/seeders/VehicleFeaturesSeeder.php"
pscp -i $HostidoKey -P 64321 "$LocalBase\database\seeders\VehicleTemplatesSeeder.php" "$RemoteBase/database/seeders/VehicleTemplatesSeeder.php"

# 4. Run migrations
Write-Host "[4/5] Running migrations..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

# 5. Clear cache
Write-Host "[5/5] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
