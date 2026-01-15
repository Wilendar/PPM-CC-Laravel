# Deploy BlockBuilderCanvas.php to Hostido
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalFile = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\VisualDescription\BlockBuilder\BlockBuilderCanvas.php"
$RemotePath = "domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/VisualDescription/BlockBuilder/BlockBuilderCanvas.php"

Write-Host "Uploading BlockBuilderCanvas.php..." -ForegroundColor Cyan
& pscp -i $HostidoKey -P 64321 $LocalFile "host379076@host379076.hostido.net.pl:$RemotePath"

if ($LASTEXITCODE -eq 0) {
    Write-Host "Upload SUCCESS" -ForegroundColor Green

    # Clear cache
    Write-Host "Clearing cache..." -ForegroundColor Cyan
    & plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan view:clear && php artisan config:clear"

    Write-Host "Done!" -ForegroundColor Green
} else {
    Write-Host "Upload FAILED" -ForegroundColor Red
}
