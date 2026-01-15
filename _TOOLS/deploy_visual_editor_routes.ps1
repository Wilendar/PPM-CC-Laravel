# Deploy Visual Editor Routes and Views
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying Visual Editor Routes/Views" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Create remote directory if needed
Write-Host "Creating remote directories..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "mkdir -p $RemotePath/resources/views/admin/visual-editor"

$filesToDeploy = @(
    "routes/web.php",
    "resources/views/admin/visual-editor/product-editor.blade.php"
)

foreach ($file in $filesToDeploy) {
    $localFile = Join-Path $LocalPath $file
    $remoteDir = Split-Path "$RemotePath/$file" -Parent
    $remoteFile = "$RemotePath/$file"

    if (Test-Path $localFile) {
        Write-Host "Uploading: $file" -ForegroundColor Yellow
        pscp -i $HostidoKey -P $RemotePort $localFile "$RemoteHost`:$remoteFile"
    } else {
        Write-Host "MISSING: $file" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Clearing route cache..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan route:clear && php artisan view:clear && php artisan cache:clear"

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Deployment Complete!" -ForegroundColor Green
