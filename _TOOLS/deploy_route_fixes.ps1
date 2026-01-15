# Deploy Route Fixes - ETAP_07f Faza 6

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Deploying Route Fixes" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Create remote directories
Write-Host "`n[DIR] Creating directories on server..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "mkdir -p $RemoteBase/resources/views/admin/visual-editor"

# Files to deploy
$files = @(
    @{
        Local = "routes\web.php"
        Remote = "routes/web.php"
        Desc = "web.php (fixed routes)"
    },
    @{
        Local = "resources\views\admin\visual-editor\product-editor.blade.php"
        Remote = "resources/views/admin/visual-editor/product-editor.blade.php"
        Desc = "product-editor.blade.php (NEW)"
    },
    @{
        Local = "resources\views\admin\product-parameters.blade.php"
        Remote = "resources/views/admin/product-parameters.blade.php"
        Desc = "product-parameters.blade.php (NEW)"
    }
)

$successCount = 0
$failCount = 0

foreach ($file in $files) {
    $localPath = Join-Path $LocalBase $file.Local
    $remotePath = "$RemoteBase/$($file.Remote)"

    if (Test-Path $localPath) {
        Write-Host "`n[UPLOADING] $($file.Desc)" -ForegroundColor Yellow
        & pscp -i $HostidoKey -P 64321 $localPath "host379076@host379076.hostido.net.pl:$remotePath" 2>&1

        if ($LASTEXITCODE -eq 0) {
            Write-Host "[OK] Uploaded successfully" -ForegroundColor Green
            $successCount++
        } else {
            Write-Host "[ERROR] Upload failed!" -ForegroundColor Red
            $failCount++
        }
    } else {
        Write-Host "[SKIP] File not found: $localPath" -ForegroundColor Red
        $failCount++
    }
}

# Clear cache
Write-Host "`n[CACHE] Clearing Laravel caches..." -ForegroundColor Yellow
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan route:clear && php artisan view:clear && php artisan cache:clear && php artisan config:clear" 2>&1

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "DEPLOYMENT SUMMARY" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Success: $successCount files" -ForegroundColor Green
if ($failCount -gt 0) {
    Write-Host "Failed: $failCount files" -ForegroundColor Red
}
Write-Host "`nTest URL: https://ppm.mpptrade.pl/admin/products/11148/edit" -ForegroundColor Magenta
