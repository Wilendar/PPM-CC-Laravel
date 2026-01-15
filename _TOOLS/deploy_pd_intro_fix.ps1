# Deploy pd-intro template fix with picture/srcset/sizes
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"

Write-Host "Uploading BlockBuilderCanvas.php..." -ForegroundColor Cyan
& pscp -i $HostidoKey -P 64321 "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\VisualDescription\BlockBuilder\BlockBuilderCanvas.php" "host379076@host379076.hostido.net.pl:$RemoteBase/app/Http/Livewire/Products/VisualDescription/BlockBuilder/BlockBuilderCanvas.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "OK" -ForegroundColor Green
} else {
    Write-Host "FAILED" -ForegroundColor Red
    exit 1
}

# Clear cache
Write-Host "Clearing cache..." -ForegroundColor Cyan
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan cache:clear && php artisan view:clear"

Write-Host "Done! pd-intro template with picture/srcset/sizes deployed." -ForegroundColor Green
