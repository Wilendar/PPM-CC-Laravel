# ETAP_07 FAZA 1E - Queue Jobs Deployment Script
# Deploys 3 Queue Job classes to Hostido server

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$Port = 64321
$RemoteBasePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "ETAP_07 FAZA 1E - Queue Jobs Deployment" -ForegroundColor Cyan
Write-Host "========================================`n" -ForegroundColor Cyan

# Local files
$LocalJobsPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Jobs\PrestaShop"

$JobFiles = @(
    "SyncProductToPrestaShop.php",
    "BulkSyncProducts.php",
    "SyncCategoryToPrestaShop.php"
)

Write-Host "[1/3] Checking if Jobs/PrestaShop folder exists on server..." -ForegroundColor Yellow
$FolderCheck = plink -ssh $RemoteHost -P $Port -i $HostidoKey -batch "ls $RemoteBasePath/app/Jobs/PrestaShop 2>&1"

if ($FolderCheck -match "No such file or directory") {
    Write-Host "Jobs/PrestaShop folder missing. Creating..." -ForegroundColor Yellow
    plink -ssh $RemoteHost -P $Port -i $HostidoKey -batch "cd $RemoteBasePath/app/Jobs && mkdir -p PrestaShop && chmod 775 PrestaShop"
    Write-Host "Folder created successfully!" -ForegroundColor Green
} else {
    Write-Host "Jobs/PrestaShop folder exists!" -ForegroundColor Green
}

Write-Host "`n[2/3] Uploading Queue Job files..." -ForegroundColor Yellow

foreach ($File in $JobFiles) {
    $LocalFile = Join-Path $LocalJobsPath $File
    $RemoteFile = "$RemoteBasePath/app/Jobs/PrestaShop/$File"

    Write-Host "  Uploading $File..." -ForegroundColor White

    pscp -i $HostidoKey -P $Port $LocalFile "${RemoteHost}:${RemoteFile}"

    if ($LASTEXITCODE -eq 0) {
        Write-Host "    ✓ $File uploaded successfully" -ForegroundColor Green
    } else {
        Write-Host "    ✗ Failed to upload $File" -ForegroundColor Red
        exit 1
    }
}

Write-Host "`n[3/3] Verifying deployed files on server..." -ForegroundColor Yellow
$Verification = plink -ssh $RemoteHost -P $Port -i $HostidoKey -batch "ls -lh $RemoteBasePath/app/Jobs/PrestaShop/"
Write-Host $Verification -ForegroundColor White

Write-Host "`n========================================" -ForegroundColor Green
Write-Host "✓ Queue Jobs Deployment COMPLETED!" -ForegroundColor Green
Write-Host "========================================`n" -ForegroundColor Green

Write-Host "Deployed Files:" -ForegroundColor Cyan
foreach ($File in $JobFiles) {
    Write-Host "  ✓ app/Jobs/PrestaShop/$File" -ForegroundColor White
}

Write-Host "`nNext Steps:" -ForegroundColor Yellow
Write-Host "  1. Run migrations for queue tables (if not exists):" -ForegroundColor White
Write-Host "     php artisan queue:table" -ForegroundColor Gray
Write-Host "     php artisan queue:failed-table" -ForegroundColor Gray
Write-Host "     php artisan queue:batches-table" -ForegroundColor Gray
Write-Host "     php artisan migrate" -ForegroundColor Gray
Write-Host "`n  2. Start queue worker:" -ForegroundColor White
Write-Host "     php artisan queue:work --queue=prestashop_high,prestashop_sync" -ForegroundColor Gray
Write-Host "`n  3. Test single product sync from admin panel" -ForegroundColor White
Write-Host "`n  4. Update Plan_Projektu/ETAP_07_Prestashop_API.md to 70%" -ForegroundColor White
