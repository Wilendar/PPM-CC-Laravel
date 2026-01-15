# ETAP_07f Faza 6 - Visual Description Tab Integration Deployment
# Deploy ProductForm Visual Description integration

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteBase = "domains/ppm.mpptrade.pl/public_html"
$LocalBase = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "ETAP_07f Faza 6 - Visual Description Tab" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# Files to deploy
$files = @(
    @{
        Local = "app\Http\Livewire\Products\Management\Traits\ProductFormVisualDescription.php"
        Remote = "app/Http/Livewire/Products/Management/Traits/ProductFormVisualDescription.php"
        Desc = "ProductFormVisualDescription trait (NEW)"
    },
    @{
        Local = "app\Http\Livewire\Products\Management\ProductForm.php"
        Remote = "app/Http/Livewire/Products/Management/ProductForm.php"
        Desc = "ProductForm.php (added trait)"
    },
    @{
        Local = "resources\views\livewire\products\management\partials\tab-navigation.blade.php"
        Remote = "resources/views/livewire/products/management/partials/tab-navigation.blade.php"
        Desc = "tab-navigation (added Opis Wizualny tab)"
    },
    @{
        Local = "resources\views\livewire\products\management\tabs\visual-description-tab.blade.php"
        Remote = "resources/views/livewire/products/management/tabs/visual-description-tab.blade.php"
        Desc = "visual-description-tab (NEW)"
    },
    @{
        Local = "resources\views\livewire\products\management\product-form.blade.php"
        Remote = "resources/views/livewire/products/management/product-form.blade.php"
        Desc = "product-form (added visual-description tab switch)"
    },
    @{
        Local = "routes\web.php"
        Remote = "routes/web.php"
        Desc = "routes (enabled visual-editor product route)"
    },
    @{
        Local = "app\Http\Livewire\Products\VisualDescription\VisualDescriptionEditor.php"
        Remote = "app/Http/Livewire/Products/VisualDescription/VisualDescriptionEditor.php"
        Desc = "VisualDescriptionEditor (fixed mount)"
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
& plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd $RemoteBase && php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan route:clear" 2>&1

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "DEPLOYMENT SUMMARY" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Success: $successCount files" -ForegroundColor Green
if ($failCount -gt 0) {
    Write-Host "Failed: $failCount files" -ForegroundColor Red
}
Write-Host "`nTest URL: https://ppm.mpptrade.pl/admin/products/11148/edit" -ForegroundColor Magenta
