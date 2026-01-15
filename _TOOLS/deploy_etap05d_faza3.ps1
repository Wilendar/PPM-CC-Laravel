# Deploy ETAP_05d FAZA 3 - Compatibility Tiles UI
# Kamil Wilinski - 2025-12-05

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "=== DEPLOYING ETAP_05d FAZA 3 - Compatibility Tiles UI ===" -ForegroundColor Cyan

# 1. Upload ALL assets (Vite regenerates hashes for all files)
Write-Host "[1/6] Uploading ALL assets..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 -r "$LocalBase\public\build\assets\*" "$RemoteBase/public/build/assets/"

# 2. Upload manifest to ROOT
Write-Host "[2/6] Uploading manifest to ROOT..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\public\build\.vite\manifest.json" "$RemoteBase/public/build/manifest.json"

# 3. Upload CompatibilityManagement.php component
Write-Host "[3/6] Uploading CompatibilityManagement.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Http\Livewire\Admin\Compatibility\CompatibilityManagement.php" "$RemoteBase/app/Http/Livewire/Admin/Compatibility/CompatibilityManagement.php"

# 4. Upload ManagesVehicleSelection trait
Write-Host "[4/6] Uploading ManagesVehicleSelection trait..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mkdir -p domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Admin/Compatibility/Traits"
pscp -i $HostidoKey -P 64321 "$LocalBase\app\Http\Livewire\Admin\Compatibility\Traits\ManagesVehicleSelection.php" "$RemoteBase/app/Http/Livewire/Admin/Compatibility/Traits/ManagesVehicleSelection.php"

# 5. Upload Blade view
Write-Host "[5/6] Uploading Blade views..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "$LocalBase\resources\views\livewire\admin\compatibility\compatibility-management.blade.php" "$RemoteBase/resources/views/livewire/admin/compatibility/compatibility-management.blade.php"

# 6. Clear cache
Write-Host "[6/6] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "Test: https://ppm.mpptrade.pl/admin/compatibility" -ForegroundColor Cyan
