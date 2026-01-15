# Deploy Visual Editor Components
# Make sure all required components are on server

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying Visual Editor Components" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Create remote directories
Write-Host "`n[DIR] Creating directories on server..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mkdir -p $RemoteBase/app/Http/Livewire/Admin/VisualEditor && mkdir -p $RemoteBase/app/Http/Livewire/Products/VisualDescription && mkdir -p $RemoteBase/app/Http/Livewire/Products/VisualDescription/Traits && mkdir -p $RemoteBase/app/Services/VisualEditor && mkdir -p $RemoteBase/app/Contracts/VisualEditor"

# Files to deploy
$files = @(
    # Visual Editor Admin Components
    "app\Http\Livewire\Admin\VisualEditor\TemplateManager.php",
    "app\Http\Livewire\Admin\VisualEditor\StylesetEditor.php",

    # Visual Description Editor
    "app\Http\Livewire\Products\VisualDescription\VisualDescriptionEditor.php",

    # Services
    "app\Services\VisualEditor\BlockRegistry.php",
    "app\Services\VisualEditor\StylesetManager.php",
    "app\Services\VisualEditor\BlockRenderer.php",
    "app\Services\VisualEditor\TemplateCategoryService.php",
    "app\Services\VisualEditor\TemplateVariableService.php",

    # Contracts
    "app\Contracts\VisualEditor\StylesetCompilerInterface.php",
    "app\Contracts\VisualEditor\ShopStylesetDefinitionInterface.php"
)

$successCount = 0
$failCount = 0

foreach ($file in $files) {
    $localPath = Join-Path $LocalBase $file
    $remotePath = "$RemoteBase/$($file -replace '\\', '/')"

    if (Test-Path $localPath) {
        Write-Host "`n[UPLOADING] $file" -ForegroundColor Yellow
        & pscp -i $HostidoKey -P 64321 $localPath "host379076@host379076.hostido.net.pl:$remotePath" 2>&1

        if ($LASTEXITCODE -eq 0) {
            Write-Host "[OK] Uploaded" -ForegroundColor Green
            $successCount++
        } else {
            Write-Host "[ERROR] Failed" -ForegroundColor Red
            $failCount++
        }
    } else {
        Write-Host "[SKIP] Not found: $file" -ForegroundColor DarkGray
    }
}

# Clear cache
Write-Host "`n[CACHE] Clearing caches..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan route:clear" 2>&1

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "DONE: $successCount uploaded, $failCount failed" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
