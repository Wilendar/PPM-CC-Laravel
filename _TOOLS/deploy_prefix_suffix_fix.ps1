# Deploy Prefix/Suffix Fix Script
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "=== DEPLOYING PREFIX/SUFFIX FIX ===" -ForegroundColor Cyan

Write-Host "[1/3] Uploading AttributeValueService.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "app/Services/Product/AttributeValueService.php" "$RemoteBase/app/Services/Product/AttributeValueService.php"

Write-Host "[2/3] Uploading attribute-value-manager.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 "resources/views/livewire/admin/variants/attribute-value-manager.blade.php" "$RemoteBase/resources/views/livewire/admin/variants/attribute-value-manager.blade.php"

Write-Host "[3/3] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
