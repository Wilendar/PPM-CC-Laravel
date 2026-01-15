# Deploy ETAP_07e FAZA 4 - PrestaShop Feature Sync
# All feature sync related files

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING ETAP_07e FAZA 4 - Feature Sync ===" -ForegroundColor Cyan

# Create remote directories if needed
Write-Host "[1/11] Creating remote directories..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && mkdir -p app/Services/PrestaShop/Transformers && mkdir -p app/Services/PrestaShop/Mappers && mkdir -p app/Jobs/Features"

# Upload PrestaShop8Client.php (updated with 12 API methods)
Write-Host "[2/11] Uploading PrestaShop8Client.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Services\PrestaShop\PrestaShop8Client.php" "$RemoteBase/app/Services/PrestaShop/PrestaShop8Client.php"

# Upload FeatureTransformer.php
Write-Host "[3/11] Uploading FeatureTransformer.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Services\PrestaShop\Transformers\FeatureTransformer.php" "$RemoteBase/app/Services/PrestaShop/Transformers/FeatureTransformer.php"

# Upload FeatureValueMapper.php
Write-Host "[4/11] Uploading FeatureValueMapper.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Services\PrestaShop\Mappers\FeatureValueMapper.php" "$RemoteBase/app/Services/PrestaShop/Mappers/FeatureValueMapper.php"

# Upload PrestaShopFeatureSyncService.php
Write-Host "[5/11] Uploading PrestaShopFeatureSyncService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Services\PrestaShop\PrestaShopFeatureSyncService.php" "$RemoteBase/app/Services/PrestaShop/PrestaShopFeatureSyncService.php"

# Upload FeatureMappingManager.php
Write-Host "[6/11] Uploading FeatureMappingManager.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Services\PrestaShop\FeatureMappingManager.php" "$RemoteBase/app/Services/PrestaShop/FeatureMappingManager.php"

# Upload SyncFeaturesJob.php
Write-Host "[7/11] Uploading SyncFeaturesJob.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Jobs\Features\SyncFeaturesJob.php" "$RemoteBase/app/Jobs/Features/SyncFeaturesJob.php"

# Upload ImportFeaturesFromPSJob.php
Write-Host "[8/11] Uploading ImportFeaturesFromPSJob.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Jobs\Features\ImportFeaturesFromPSJob.php" "$RemoteBase/app/Jobs/Features/ImportFeaturesFromPSJob.php"

# Upload job_types.php config
Write-Host "[9/11] Uploading job_types.php config..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\config\job_types.php" "$RemoteBase/config/job_types.php"

# Upload ProductSyncStrategy.php (updated with syncFeaturesIfEnabled)
Write-Host "[10/11] Uploading ProductSyncStrategy.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Services\PrestaShop\Sync\ProductSyncStrategy.php" "$RemoteBase/app/Services/PrestaShop/Sync/ProductSyncStrategy.php"

# Clear cache
Write-Host "[11/11] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host ""
Write-Host "Deployed files:" -ForegroundColor Cyan
Write-Host "  - PrestaShop8Client.php (12 API methods)"
Write-Host "  - FeatureTransformer.php"
Write-Host "  - FeatureValueMapper.php"
Write-Host "  - PrestaShopFeatureSyncService.php"
Write-Host "  - FeatureMappingManager.php"
Write-Host "  - SyncFeaturesJob.php"
Write-Host "  - ImportFeaturesFromPSJob.php"
Write-Host "  - job_types.php"
Write-Host "  - ProductSyncStrategy.php (syncFeaturesIfEnabled)"
