# Deploy Variant Images Import Fix
# 2025-12-10
# Deploys VariantImageDownloadService and updated PrestaShopImportService

$ErrorActionPreference = "Stop"
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"

Write-Host "=== VARIANT IMAGES IMPORT FIX DEPLOYMENT ===" -ForegroundColor Cyan
Write-Host ""

# Files to deploy
$files = @(
    @{
        Local = "$LocalPath\app\Services\Media\VariantImageDownloadService.php"
        Remote = "$RemotePath/app/Services/Media/VariantImageDownloadService.php"
        Description = "NEW: VariantImageDownloadService"
    },
    @{
        Local = "$LocalPath\app\Services\PrestaShop\PrestaShopImportService.php"
        Remote = "$RemotePath/app/Services/PrestaShop/PrestaShopImportService.php"
        Description = "UPDATED: PrestaShopImportService (importVariantImages)"
    }
)

# Upload files
foreach ($file in $files) {
    Write-Host "Uploading: $($file.Description)" -ForegroundColor Yellow

    # Check if local file exists
    if (!(Test-Path $file.Local)) {
        Write-Host "  ERROR: Local file not found: $($file.Local)" -ForegroundColor Red
        exit 1
    }

    # Upload file
    $pscpArgs = @("-i", $HostidoKey, "-P", $RemotePort, $file.Local, "${RemoteHost}:$($file.Remote)")
    & pscp @pscpArgs

    if ($LASTEXITCODE -eq 0) {
        Write-Host "  OK: Uploaded successfully" -ForegroundColor Green
    } else {
        Write-Host "  ERROR: Upload failed" -ForegroundColor Red
        exit 1
    }
}

Write-Host ""
Write-Host "Clearing caches..." -ForegroundColor Yellow

# Clear Laravel caches
$cacheCmd = "cd $RemotePath && php artisan config:clear && php artisan cache:clear && php artisan view:clear"
& plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch $cacheCmd

if ($LASTEXITCODE -eq 0) {
    Write-Host "  OK: Caches cleared" -ForegroundColor Green
} else {
    Write-Host "  WARNING: Cache clear may have partial errors" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETED ===" -ForegroundColor Green
Write-Host ""
Write-Host "WHAT WAS DEPLOYED:" -ForegroundColor Cyan
Write-Host "1. NEW SERVICE: VariantImageDownloadService" -ForegroundColor White
Write-Host "   - Downloads variant images from PrestaShop API" -ForegroundColor Gray
Write-Host "   - Stores to storage/app/public/variants/{variant_id}/" -ForegroundColor Gray
Write-Host ""
Write-Host "2. UPDATED: PrestaShopImportService.importVariantImages()" -ForegroundColor White
Write-Host "   - Strategy 1: Link to existing Media (if PS image ID matches)" -ForegroundColor Gray
Write-Host "   - Strategy 2: Link to Media by position" -ForegroundColor Gray
Write-Host "   - Strategy 3: Download from PrestaShop API (NEW!)" -ForegroundColor Gray
Write-Host "   - Strategy 4: Save URL only (fallback)" -ForegroundColor Gray
Write-Host ""
Write-Host "TO TEST:" -ForegroundColor Yellow
Write-Host "Import a product with variants from PrestaShop and check:" -ForegroundColor White
Write-Host "- variant_images table has image_path filled" -ForegroundColor Gray
Write-Host "- Files exist in storage/app/public/variants/{variant_id}/" -ForegroundColor Gray
Write-Host "- Laravel logs show '[VARIANT IMG DOWNLOAD] Image downloaded'" -ForegroundColor Gray
