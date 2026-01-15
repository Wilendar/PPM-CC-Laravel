# Deploy Repeater and Slide elements for Visual Block Builder
# ETAP 07f FAZA 4.2.3

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying Repeater and Slide Elements" -ForegroundColor Cyan
Write-Host "ETAP 07f FAZA 4.2.3" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Files to deploy
$files = @(
    @{
        Local = "app/Http/Livewire/Products/VisualDescription/BlockBuilder/BlockBuilderCanvas.php"
        Remote = "app/Http/Livewire/Products/VisualDescription/BlockBuilder/BlockBuilderCanvas.php"
        Description = "BlockBuilderCanvas.php (repeater/slide definitions + methods)"
    },
    @{
        Local = "resources/views/livewire/products/visual-description/block-builder/canvas.blade.php"
        Remote = "resources/views/livewire/products/visual-description/block-builder/canvas.blade.php"
        Description = "canvas.blade.php (palette buttons)"
    },
    @{
        Local = "resources/views/livewire/products/visual-description/block-builder/partials/element-renderer.blade.php"
        Remote = "resources/views/livewire/products/visual-description/block-builder/partials/element-renderer.blade.php"
        Description = "element-renderer.blade.php (repeater/slide rendering)"
    },
    @{
        Local = "resources/views/livewire/products/visual-description/block-builder/partials/property-panel.blade.php"
        Remote = "resources/views/livewire/products/visual-description/block-builder/partials/property-panel.blade.php"
        Description = "property-panel.blade.php (repeater/slide controls)"
    }
)

# Deploy each file
foreach ($file in $files) {
    $localFile = Join-Path $LocalPath $file.Local
    $remoteFile = "$RemotePath/$($file.Remote)"

    Write-Host ""
    Write-Host "Deploying: $($file.Description)" -ForegroundColor Yellow

    if (Test-Path $localFile) {
        pscp -i $HostidoKey -P $RemotePort $localFile "${RemoteHost}:${remoteFile}"
        if ($LASTEXITCODE -eq 0) {
            Write-Host "  OK: Uploaded successfully" -ForegroundColor Green
        } else {
            Write-Host "  ERROR: Upload failed!" -ForegroundColor Red
        }
    } else {
        Write-Host "  ERROR: Local file not found: $localFile" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "Clearing Laravel cache..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear"

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Deployment completed!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Test URL: https://ppm.mpptrade.pl/admin/visual-editor" -ForegroundColor Cyan
