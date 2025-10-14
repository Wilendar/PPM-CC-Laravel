# Deploy ShopManager ETAP_07 Integration
# ETAP_07 FAZA 1G - ShopManager Component Update
# Date: 2025-10-03

Write-Host "====================================" -ForegroundColor Cyan
Write-Host "DEPLOY: ShopManager ETAP_07 Integration" -ForegroundColor Cyan
Write-Host "FAZA 1G: Livewire UI Extensions" -ForegroundColor Cyan
Write-Host "====================================" -ForegroundColor Cyan
Write-Host ""

# SSH Key Path
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalRoot = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemoteRoot = "domains/ppm.mpptrade.pl/public_html"

# Files to deploy
$files = @(
    @{
        Local = "$LocalRoot\app\Http\Livewire\Admin\Shops\ShopManager.php"
        Remote = "$RemoteRoot/app/Http/Livewire/Admin/Shops/ShopManager.php"
        Description = "ShopManager Component (ETAP_07 Integration)"
    }
)

Write-Host "Files to deploy:" -ForegroundColor Yellow
foreach ($file in $files) {
    Write-Host "  - $($file.Description)" -ForegroundColor White
}
Write-Host ""

# Confirm deployment
$confirm = Read-Host "Proceed with deployment? (Y/N)"
if ($confirm -ne 'Y' -and $confirm -ne 'y') {
    Write-Host "Deployment cancelled." -ForegroundColor Red
    exit
}

Write-Host ""
Write-Host "Starting deployment..." -ForegroundColor Green
Write-Host ""

# Deploy each file
$successCount = 0
$failCount = 0

foreach ($file in $files) {
    Write-Host "Uploading: $($file.Description)..." -ForegroundColor Cyan

    try {
        # Upload file
        pscp -i $HostidoKey -P 64321 `
            $file.Local `
            "host379076@host379076.hostido.net.pl:$($file.Remote)" 2>&1 | Out-Null

        if ($LASTEXITCODE -eq 0) {
            Write-Host "  ✓ SUCCESS" -ForegroundColor Green
            $successCount++
        } else {
            Write-Host "  ✗ FAILED (exit code: $LASTEXITCODE)" -ForegroundColor Red
            $failCount++
        }
    } catch {
        Write-Host "  ✗ EXCEPTION: $($_.Exception.Message)" -ForegroundColor Red
        $failCount++
    }
}

Write-Host ""
Write-Host "Upload Summary:" -ForegroundColor Yellow
Write-Host "  Success: $successCount / $($files.Count)" -ForegroundColor Green
Write-Host "  Failed:  $failCount / $($files.Count)" -ForegroundColor $(if ($failCount -gt 0) { "Red" } else { "Green" })
Write-Host ""

if ($failCount -gt 0) {
    Write-Host "Deployment completed with errors. Skipping cache clear." -ForegroundColor Red
    exit 1
}

# Clear caches
Write-Host "Clearing Laravel caches..." -ForegroundColor Cyan

$cacheCommands = @(
    "php artisan view:clear",
    "php artisan cache:clear",
    "php artisan config:clear"
)

foreach ($cmd in $cacheCommands) {
    Write-Host "  Running: $cmd..." -ForegroundColor White

    try {
        plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
            "cd $RemoteRoot && $cmd" 2>&1 | Out-Null

        if ($LASTEXITCODE -eq 0) {
            Write-Host "    ✓ OK" -ForegroundColor Green
        } else {
            Write-Host "    ⚠ WARNING (exit code: $LASTEXITCODE)" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "    ✗ ERROR: $($_.Exception.Message)" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "====================================" -ForegroundColor Cyan
Write-Host "DEPLOYMENT COMPLETED SUCCESSFULLY!" -ForegroundColor Green
Write-Host "====================================" -ForegroundColor Cyan
Write-Host ""

# Verification instructions
Write-Host "Next Steps - VERIFICATION:" -ForegroundColor Yellow
Write-Host ""
Write-Host "1. Open browser: https://ppm.mpptrade.pl/admin/shops" -ForegroundColor White
Write-Host "2. Login: admin@mpptrade.pl / Admin123!MPP" -ForegroundColor White
Write-Host "3. Test operations:" -ForegroundColor White
Write-Host "   - Test Connection (should use PrestaShopSyncService)" -ForegroundColor Gray
Write-Host "   - Sync Shop (should queue BulkSyncProducts)" -ForegroundColor Gray
Write-Host "   - View Sync Statistics (new method)" -ForegroundColor Gray
Write-Host "   - Retry Failed Syncs (new method)" -ForegroundColor Gray
Write-Host "   - View Sync Logs (new method)" -ForegroundColor Gray
Write-Host ""
Write-Host "4. Check logs on server:" -ForegroundColor White
Write-Host "   ssh -p 64321 host379076@host379076.hostido.net.pl" -ForegroundColor Gray
Write-Host "   tail -f domains/ppm.mpptrade.pl/public_html/storage/logs/laravel.log" -ForegroundColor Gray
Write-Host ""
Write-Host "Expected log entries:" -ForegroundColor White
Write-Host "   - 'Bulk sync queued from ShopManager'" -ForegroundColor Gray
Write-Host "   - 'Testing PrestaShop connection'" -ForegroundColor Gray
Write-Host "   - 'Sync statistics viewed'" -ForegroundColor Gray
Write-Host ""

# Report location
Write-Host "Full report available at:" -ForegroundColor Yellow
Write-Host "  _AGENT_REPORTS\SHOPMANAGER_ETAP07_INTEGRATION_REPORT.md" -ForegroundColor Cyan
Write-Host ""
