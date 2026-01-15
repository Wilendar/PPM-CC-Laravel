$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING FAZA 2 FEATURES + BulkAssignJob ===" -ForegroundColor Cyan

Write-Host "[1/5] Creating Jobs/Features folder..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mkdir -p domains/ppm.mpptrade.pl/public_html/app/Jobs/Features"

Write-Host "[2/5] Uploading BulkAssignFeaturesJob.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Jobs\Features\BulkAssignFeaturesJob.php" "$RemoteBase/app/Jobs/Features/BulkAssignFeaturesJob.php"

Write-Host "[3/5] Uploading VehicleFeatureManagement.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Http\Livewire\Admin\Features\VehicleFeatureManagement.php" "$RemoteBase/app/Http/Livewire/Admin/Features/VehicleFeatureManagement.php"

Write-Host "[4/5] Uploading vehicle-feature-management.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\resources\views\livewire\admin\features\vehicle-feature-management.blade.php" "$RemoteBase/resources/views/livewire/admin/features/vehicle-feature-management.blade.php"

Write-Host "[5/5] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
