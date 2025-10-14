# Deploy import fix to production
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$HostidoHost = "host379076@host379076.hostido.net.pl"
$HostidoPort = "64321"
$LocalPath = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== DEPLOYING FIX TO PRODUCTION ===" -ForegroundColor Cyan

Write-Host "`n[1/4] Uploading AnalyzeMissingCategories.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P $HostidoPort `
    "$LocalPath\app\Jobs\PrestaShop\AnalyzeMissingCategories.php" `
    "${HostidoHost}:${RemotePath}/app/Jobs/PrestaShop/AnalyzeMissingCategories.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "✓ File uploaded successfully" -ForegroundColor Green
} else {
    Write-Host "✗ Upload failed!" -ForegroundColor Red
    exit 1
}

Write-Host "`n[2/4] Clearing cache..." -ForegroundColor Yellow
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch `
    "cd $RemotePath && php artisan cache:clear && php artisan view:clear"

Write-Host "`n[3/4] Restarting queue workers..." -ForegroundColor Yellow
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch `
    "cd $RemotePath && php artisan queue:restart"

Write-Host "`n[4/4] Verifying deployed code..." -ForegroundColor Yellow
plink -ssh $HostidoHost -P $HostidoPort -i $HostidoKey -batch `
    "cd $RemotePath && grep -A 3 'FIX: Handle empty tree' app/Jobs/PrestaShop/AnalyzeMissingCategories.php"

Write-Host "`n=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
Write-Host "`nNext steps:" -ForegroundColor Cyan
Write-Host "1. Flush failed jobs: php artisan queue:flush" -ForegroundColor White
Write-Host "2. Test import from UI: https://ppm.mpptrade.pl/admin/products" -ForegroundColor White
Write-Host "3. Monitor logs for errors" -ForegroundColor White
