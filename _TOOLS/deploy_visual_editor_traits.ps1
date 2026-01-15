# Deploy Visual Editor Traits and additional components

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying Visual Editor Traits" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Create remote directories
Write-Host "`n[DIR] Creating directories..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mkdir -p $RemoteBase/app/Http/Livewire/Products/VisualDescription/Traits && mkdir -p $RemoteBase/app/Services/VisualEditor/Blocks"

# Files to deploy
$files = @(
    "app\Http\Livewire\Products\VisualDescription\Traits\EditorBlockManagement.php",
    "app\Http\Livewire\Products\VisualDescription\Traits\EditorUndoRedo.php",
    "app\Http\Livewire\Products\VisualDescription\Traits\EditorPreview.php",
    "app\Http\Livewire\Products\VisualDescription\Traits\EditorTemplates.php"
)

$successCount = 0

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
        }
    } else {
        Write-Host "[SKIP] Not found: $file" -ForegroundColor DarkGray
    }
}

# Clear cache
Write-Host "`n[CACHE] Clearing caches..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan route:clear" 2>&1

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "DONE: $successCount uploaded" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
