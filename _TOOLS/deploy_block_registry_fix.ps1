# Deploy Block Registry Fix - loadShopBlocks for dynamic blocks
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying Block Registry Fix" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

$filesToDeploy = @(
    "app/Http/Livewire/Products/VisualDescription/VisualDescriptionEditor.php",
    "app/Http/Livewire/Products/VisualDescription/BlockGeneratorModal.php"
)

foreach ($file in $filesToDeploy) {
    $localFile = Join-Path $LocalPath $file
    $remoteFile = "$RemotePath/$file"

    if (Test-Path $localFile) {
        Write-Host "Uploading: $file" -ForegroundColor Yellow
        pscp -i $HostidoKey -P $RemotePort $localFile "$RemoteHost`:$remoteFile"
    } else {
        Write-Host "MISSING: $file" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Clearing caches..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear"

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Deployment Complete!" -ForegroundColor Green
