# Deployment Script - ETAP_07f_P3 FAZA 2: Dedicated Blocks System
# Date: 2025-12-17

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ETAP_07f_P3 FAZA 2 Deployment" -ForegroundColor Cyan
Write-Host "Dedicated Blocks System" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Create remote directories if needed
Write-Host "Creating remote directories..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "mkdir -p $RemotePath/app/Services/VisualEditor/BlockGenerator"
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "mkdir -p $RemotePath/app/Services/VisualEditor/Blocks"

# Files to deploy
$filesToDeploy = @(
    # Migration
    @{Local="database/migrations/2025_12_17_100002_create_block_definitions_table.php"; Remote="database/migrations/"},

    # Services - BlockGenerator
    @{Local="app/Services/VisualEditor/BlockGenerator/BlockAutoGenerator.php"; Remote="app/Services/VisualEditor/BlockGenerator/"},
    @{Local="app/Services/VisualEditor/BlockGenerator/BlockAnalysisResult.php"; Remote="app/Services/VisualEditor/BlockGenerator/"},

    # Services - DynamicBlock
    @{Local="app/Services/VisualEditor/Blocks/DynamicBlock.php"; Remote="app/Services/VisualEditor/Blocks/"},

    # Services - BlockRegistry (modified)
    @{Local="app/Services/VisualEditor/BlockRegistry.php"; Remote="app/Services/VisualEditor/"},

    # Livewire Components
    @{Local="app/Http/Livewire/Products/VisualDescription/BlockGeneratorModal.php"; Remote="app/Http/Livewire/Products/VisualDescription/"},
    @{Local="app/Http/Livewire/Products/VisualDescription/VisualDescriptionEditor.php"; Remote="app/Http/Livewire/Products/VisualDescription/"},

    # Views
    @{Local="resources/views/livewire/products/visual-description/partials/block-generator-modal.blade.php"; Remote="resources/views/livewire/products/visual-description/partials/"},
    @{Local="resources/views/livewire/products/visual-description/partials/block-canvas.blade.php"; Remote="resources/views/livewire/products/visual-description/partials/"},
    @{Local="resources/views/livewire/products/visual-description/visual-description-editor.blade.php"; Remote="resources/views/livewire/products/visual-description/"}
)

# Upload files
Write-Host ""
Write-Host "Uploading files..." -ForegroundColor Yellow
foreach ($file in $filesToDeploy) {
    $localFile = Join-Path $LocalPath $file.Local
    $remoteFile = "$RemotePath/$($file.Remote)"

    if (Test-Path $localFile) {
        Write-Host "  Uploading: $($file.Local)" -ForegroundColor Gray
        pscp -i $HostidoKey -P $RemotePort $localFile "$RemoteHost`:$remoteFile"
    } else {
        Write-Host "  MISSING: $($file.Local)" -ForegroundColor Red
    }
}

# Clear caches
Write-Host ""
Write-Host "Clearing caches..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && php artisan view:clear && php artisan cache:clear && php artisan config:clear"

# Dump autoloader
Write-Host ""
Write-Host "Refreshing autoloader..." -ForegroundColor Yellow
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch "cd $RemotePath && composer dump-autoload"

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Deployment Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host "1. Run migration: php artisan migrate" -ForegroundColor White
Write-Host "2. Test on: https://ppm.mpptrade.pl" -ForegroundColor White
