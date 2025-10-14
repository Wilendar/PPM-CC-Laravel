# Deploy ETAP_07 FAZA 3 - Sync Status UI Implementation
# Author: Claude Code (Frontend Specialist)
# Date: 2025-10-06
# Description: Deploy sync status badges and UI components to Hostido

[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$PSDefaultParameterValues['*:Encoding'] = 'utf8'

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "ETAP_07 FAZA 3: Sync Status UI Deployment" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# SSH Configuration
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = 64321
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalRoot = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

# Files to deploy
$FilesToDeploy = @(
    @{
        Local = "$LocalRoot\app\Http\Livewire\Products\Listing\ProductList.php"
        Remote = "$RemotePath/app/Http/Livewire/Products/Listing/ProductList.php"
        Description = "ProductList component with syncStatuses eager loading"
    },
    @{
        Local = "$LocalRoot\resources\views\livewire\products\listing\product-list.blade.php"
        Remote = "$RemotePath/resources/views/livewire/products/listing/product-list.blade.php"
        Description = "Product List UI with sync status badges"
    },
    @{
        Local = "$LocalRoot\resources\css\admin\components.css"
        Remote = "$RemotePath/resources/css/admin/components.css"
        Description = "Admin components CSS with sync status badges"
    }
)

# Step 1: Upload files
Write-Host "[1/4] Uploading files to Hostido..." -ForegroundColor Yellow

foreach ($file in $FilesToDeploy) {
    Write-Host "  -> Uploading: $($file.Description)" -ForegroundColor Gray

    if (Test-Path $file.Local) {
        pscp -i $HostidoKey -P $HostidoPort $file.Local "${HostidoHost}:$($file.Remote)" 2>&1 | Out-Null

        if ($LASTEXITCODE -eq 0) {
            Write-Host "     [OK] $($file.Local | Split-Path -Leaf)" -ForegroundColor Green
        } else {
            Write-Host "     [ERROR] Failed to upload $($file.Local | Split-Path -Leaf)" -ForegroundColor Red
            exit 1
        }
    } else {
        Write-Host "     [ERROR] File not found: $($file.Local)" -ForegroundColor Red
        exit 1
    }
}

# Step 2: Build assets on server (if vite is available)
Write-Host "`n[2/4] Checking if assets need rebuilding..." -ForegroundColor Yellow

$buildCommand = @"
cd $RemotePath
if [ -f package.json ] && [ -d node_modules ]; then
    npm run build 2>&1 && echo 'VITE_BUILD_SUCCESS' || echo 'VITE_BUILD_SKIP'
else
    echo 'VITE_BUILD_SKIP'
fi
"@

$buildResult = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch $buildCommand 2>&1

if ($buildResult -like "*VITE_BUILD_SUCCESS*") {
    Write-Host "  [OK] Assets built successfully" -ForegroundColor Green
} else {
    Write-Host "  [INFO] Asset build skipped (build locally with 'npm run build' if needed)" -ForegroundColor Yellow
}

# Step 3: Clear caches
Write-Host "`n[3/4] Clearing Laravel caches..." -ForegroundColor Yellow

$cacheCommands = @(
    "cd $RemotePath && php artisan view:clear",
    "cd $RemotePath && php artisan cache:clear",
    "cd $RemotePath && php artisan config:clear"
)

foreach ($cmd in $cacheCommands) {
    $result = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch $cmd 2>&1

    if ($LASTEXITCODE -eq 0) {
        Write-Host "  [OK] $($cmd -replace '.*artisan ', '')" -ForegroundColor Green
    } else {
        Write-Host "  [WARNING] Cache clear may have failed: $($cmd -replace '.*artisan ', '')" -ForegroundColor Yellow
    }
}

# Step 4: Verify deployment
Write-Host "`n[4/4] Verifying deployment..." -ForegroundColor Yellow

$verifyCommand = @"
cd $RemotePath
echo "Checking ProductList.php..."
if grep -q "syncStatuses.shop" app/Http/Livewire/Products/Listing/ProductList.php; then
    echo "VERIFY_COMPONENT_OK"
else
    echo "VERIFY_COMPONENT_FAIL"
fi

echo "Checking product-list.blade.php..."
if grep -q "sync-status-badge" resources/views/livewire/products/listing/product-list.blade.php; then
    echo "VERIFY_BLADE_OK"
else
    echo "VERIFY_BLADE_FAIL"
fi

echo "Checking components.css..."
if grep -q "sync-status-synced" resources/css/admin/components.css; then
    echo "VERIFY_CSS_OK"
else
    echo "VERIFY_CSS_FAIL"
fi
"@

$verifyResult = plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch $verifyCommand 2>&1

$allVerified = $true

if ($verifyResult -like "*VERIFY_COMPONENT_OK*") {
    Write-Host "  [OK] ProductList component deployed" -ForegroundColor Green
} else {
    Write-Host "  [ERROR] ProductList component verification failed" -ForegroundColor Red
    $allVerified = $false
}

if ($verifyResult -like "*VERIFY_BLADE_OK*") {
    Write-Host "  [OK] Blade template deployed" -ForegroundColor Green
} else {
    Write-Host "  [ERROR] Blade template verification failed" -ForegroundColor Red
    $allVerified = $false
}

if ($verifyResult -like "*VERIFY_CSS_OK*") {
    Write-Host "  [OK] CSS components deployed" -ForegroundColor Green
} else {
    Write-Host "  [ERROR] CSS verification failed" -ForegroundColor Red
    $allVerified = $false
}

# Summary
Write-Host "`n========================================" -ForegroundColor Cyan
if ($allVerified) {
    Write-Host "DEPLOYMENT SUCCESSFUL!" -ForegroundColor Green
    Write-Host "`nNext steps:" -ForegroundColor Yellow
    Write-Host "  1. Visit: https://ppm.mpptrade.pl/products" -ForegroundColor White
    Write-Host "  2. Check Product List - 'PrestaShop Sync' column" -ForegroundColor White
    Write-Host "  3. Hover over sync badges to see tooltips" -ForegroundColor White
    Write-Host "  4. Verify status icons: synced (green), pending (blue), error (red)" -ForegroundColor White
} else {
    Write-Host "DEPLOYMENT COMPLETED WITH WARNINGS" -ForegroundColor Yellow
    Write-Host "Please verify manually on production" -ForegroundColor Yellow
}
Write-Host "========================================`n" -ForegroundColor Cyan
