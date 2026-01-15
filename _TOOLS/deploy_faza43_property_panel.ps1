# Deploy FAZA 4.3 Property Panel enhancements for Visual Block Builder
# SpacingControl, BorderPanel, EffectsPanel, Position selector, SizePicker

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying FAZA 4.3 Property Panel" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Files to deploy
$files = @(
    @{
        Local = "app/Http/Livewire/Products/VisualDescription/BlockBuilder/BlockBuilderCanvas.php"
        Remote = "app/Http/Livewire/Products/VisualDescription/BlockBuilder/BlockBuilderCanvas.php"
        Description = "BlockBuilderCanvas.php (CSS mapping update)"
    },
    @{
        Local = "resources/views/livewire/products/visual-description/block-builder/canvas.blade.php"
        Remote = "resources/views/livewire/products/visual-description/block-builder/canvas.blade.php"
        Description = "canvas.blade.php (sizePicker JS component)"
    },
    @{
        Local = "resources/views/livewire/products/visual-description/block-builder/partials/property-panel.blade.php"
        Remote = "resources/views/livewire/products/visual-description/block-builder/partials/property-panel.blade.php"
        Description = "property-panel.blade.php (SpacingControl, Border, Effects, Position)"
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
Write-Host "Test URL: https://ppm.mpptrade.pl/admin/visual-editor/product/11183/shop/1" -ForegroundColor Cyan
Write-Host "Click 'Stworz blok wizualnie' to open BlockBuilder" -ForegroundColor Cyan
