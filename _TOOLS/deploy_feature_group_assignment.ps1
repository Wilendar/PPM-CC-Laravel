$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING FEATURE GROUP ASSIGNMENT ===" -ForegroundColor Cyan

Write-Host "[1/6] Uploading PrestaShopImportService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Services\PrestaShop\PrestaShopImportService.php" "${RemoteBase}/app/Services/PrestaShop/PrestaShopImportService.php"

Write-Host "[2/6] Uploading ProductFormFeatures.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\Management\Traits\ProductFormFeatures.php" "${RemoteBase}/app/Http/Livewire/Products/Management/Traits/ProductFormFeatures.php"

Write-Host "[3/6] Uploading attributes-tab.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\tabs\attributes-tab.blade.php" "${RemoteBase}/resources/views/livewire/products/management/tabs/attributes-tab.blade.php"

Write-Host "[4/6] Uploading rename script..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\_TEMP\rename_feature_group.php" "${RemoteBase}/_TEMP/rename_feature_group.php"

Write-Host "[5/6] Running rename script..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php _TEMP/rename_feature_group.php && rm _TEMP/rename_feature_group.php"

Write-Host "[6/6] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan view:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
