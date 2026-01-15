$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"

Write-Host "Deploying CssJsEditorModal.php with category fix..." -ForegroundColor Cyan

# Upload Livewire component
Write-Host "Uploading CssJsEditorModal.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $RemotePort "$LocalPath\app\Http\Livewire\Products\VisualDescription\CssJsEditorModal.php" "${RemoteHost}:${RemotePath}/app/Http/Livewire/Products/VisualDescription/CssJsEditorModal.php"

# Clear cache
Write-Host "Clearing cache..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear"

Write-Host "Deployment complete!" -ForegroundColor Green
