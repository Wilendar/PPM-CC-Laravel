$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"

Write-Host "Deploying CSS/JS Editor Modal files..." -ForegroundColor Cyan

# 1. Upload Blade template
Write-Host "Uploading Blade template..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort "$LocalPath\resources\views\livewire\products\visual-description\partials\css-js-editor-modal.blade.php" "${RemoteHost}:${RemotePath}/resources/views/livewire/products/visual-description/partials/css-js-editor-modal.blade.php"

# 2. Upload Livewire component
Write-Host "Uploading Livewire component..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort "$LocalPath\app\Http\Livewire\Products\VisualDescription\CssJsEditorModal.php" "${RemoteHost}:${RemotePath}/app/Http/Livewire/Products/VisualDescription/CssJsEditorModal.php"

# 3. Upload PrestaShopCssFetcher service
Write-Host "Uploading PrestaShopCssFetcher service..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort "$LocalPath\app\Services\VisualEditor\PrestaShopCssFetcher.php" "${RemoteHost}:${RemotePath}/app/Services/VisualEditor/PrestaShopCssFetcher.php"

# 4. Clear cache
Write-Host "Clearing cache..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

Write-Host "Deployment complete!" -ForegroundColor Green
