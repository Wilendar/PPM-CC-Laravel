# Deploy ETAP_07 FAZA 3: Widoczny status sync + Import UI
# Backend Logic + UI Components

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalRoot = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemoteRoot = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== ETAP_07 FAZA 3 DEPLOYMENT ===" -ForegroundColor Cyan
Write-Host "Deploying: Sync Status UI + Import Modal" -ForegroundColor White
Write-Host ""

# File list
$files = @(
    @{
        Local = "$LocalRoot\app\Http\Livewire\Products\Management\ProductForm.php"
        Remote = "$RemoteRoot/app/Http/Livewire/Products/Management/ProductForm.php"
        Name = "ProductForm.php (Backend Logic)"
    },
    @{
        Local = "$LocalRoot\app\Models\Product.php"
        Remote = "$RemoteRoot/app/Models/Product.php"
        Name = "Product.php (Model Relation)"
    },
    @{
        Local = "$LocalRoot\resources\views\livewire\products\management\product-form.blade.php"
        Remote = "$RemoteRoot/resources/views/livewire/products/management/product-form.blade.php"
        Name = "product-form.blade.php (UI Components)"
    }
)

# Upload files
Write-Host "Uploading files..." -ForegroundColor Yellow
foreach ($file in $files) {
    Write-Host "  → $($file.Name)" -ForegroundColor Gray

    & "C:\Program Files\PuTTY\pscp.exe" -i $HostidoKey -P 64321 `
        $file.Local `
        "host379076@host379076.hostido.net.pl:$($file.Remote)" 2>&1 | Out-Null

    if ($LASTEXITCODE -eq 0) {
        Write-Host "    ✅ Uploaded" -ForegroundColor Green
    } else {
        Write-Host "    ❌ Failed!" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "Clearing caches..." -ForegroundColor Yellow

# Clear all caches
& "C:\Program Files\PuTTY\plink.exe" -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch @"
cd $RemoteRoot && php -r 'if (function_exists(""opcache_reset"")) opcache_reset();' && rm -rf storage/framework/views/* && php artisan view:clear && php artisan cache:clear && php artisan config:clear && php artisan optimize:clear
"@

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETED ===" -ForegroundColor Green
Write-Host ""
Write-Host "✅ ProductForm.php - Backend methods deployed" -ForegroundColor White
Write-Host "✅ Product.php - Model relation deployed" -ForegroundColor White
Write-Host "✅ product-form.blade.php - UI components deployed" -ForegroundColor White
Write-Host "✅ All caches cleared" -ForegroundColor White
Write-Host ""
Write-Host "🔍 Verify at: https://ppm.mpptrade.pl/admin/products/4/edit" -ForegroundColor Cyan
Write-Host ""
Write-Host "⚠️ NEXT STEP: Start queue worker!" -ForegroundColor Yellow
Write-Host "   php artisan queue:work --tries=3 --timeout=300" -ForegroundColor Gray
