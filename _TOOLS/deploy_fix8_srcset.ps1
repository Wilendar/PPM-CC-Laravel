$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "Deploying FIX #8 - srcset removal in UVE_MediaPicker.php" -ForegroundColor Green

pscp -i $HostidoKey -P 64321 "D:\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\VisualDescription\Traits\UVE_MediaPicker.php" "${RemoteHost}:${RemotePath}/app/Http/Livewire/Products/VisualDescription/Traits/UVE_MediaPicker.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "File uploaded successfully!" -ForegroundColor Green

    # Clear cache
    Write-Host "Clearing cache..." -ForegroundColor Yellow
    plink -ssh $RemoteHost -P 64321 -i $HostidoKey -batch "cd $RemotePath && php artisan cache:clear && php artisan view:clear"

    Write-Host "Deployment complete!" -ForegroundColor Green
} else {
    Write-Host "Upload failed with exit code: $LASTEXITCODE" -ForegroundColor Red
}
