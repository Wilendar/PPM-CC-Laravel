# Deploy CSS columns migration for ETAP_07h
# Run: .\deploy_css_columns_migration.ps1

$ErrorActionPreference = "Stop"

$HostidoKey = "D:\SSH\Hostido\HostidoSSHNoPass.ppk"
$RemoteHost = "host379076@host379076.hostido.net.pl"
$RemotePort = "64321"
$RemotePath = "domains/ppm.mpptrade.pl/public_html"

Write-Host "=== Deploying CSS Columns Migration (ETAP_07h) ===" -ForegroundColor Cyan

# 1. Upload migration file
Write-Host "`n[1/4] Uploading migration file..." -ForegroundColor Yellow
$migrationFile = "database\migrations\2026_01_09_100001_add_css_columns_to_product_descriptions.php"
pscp -i $HostidoKey -P $RemotePort "$migrationFile" "${RemoteHost}:${RemotePath}/database/migrations/"
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload migration file" -ForegroundColor Red
    exit 1
}
Write-Host "Migration file uploaded" -ForegroundColor Green

# 2. Upload updated model
Write-Host "`n[2/4] Uploading ProductDescription model..." -ForegroundColor Yellow
$modelFile = "app\Models\ProductDescription.php"
pscp -i $HostidoKey -P $RemotePort "$modelFile" "${RemoteHost}:${RemotePath}/app/Models/"
if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload model" -ForegroundColor Red
    exit 1
}
Write-Host "Model uploaded" -ForegroundColor Green

# 3. Run migration
Write-Host "`n[3/4] Running migration on server..." -ForegroundColor Yellow
$migrateCmd = "cd $RemotePath && php artisan migrate --force 2>&1"
$result = plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch $migrateCmd
Write-Host $result
if ($result -like "*FAIL*" -or $result -like "*Error*") {
    Write-Host "WARNING: Migration may have issues" -ForegroundColor Yellow
}

# 4. Clear caches
Write-Host "`n[4/4] Clearing caches..." -ForegroundColor Yellow
$cacheCmd = "cd $RemotePath && php artisan config:clear && php artisan cache:clear"
plink -ssh $RemoteHost -P $RemotePort -i $HostidoKey -batch $cacheCmd | Out-Null
Write-Host "Caches cleared" -ForegroundColor Green

Write-Host "`n=== Deployment Complete ===" -ForegroundColor Green
Write-Host "Verify with: php artisan tinker --execute=""Schema::hasColumn('product_descriptions', 'css_rules')"""
