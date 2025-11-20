$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== UPLOADING FIXED MIGRATION ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "Uploading fixed variant_attributes migration..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
  "database/migrations/2025_10_28_000001_refactor_variant_attributes_value_id.php" `
  "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/database/migrations/2025_10_28_000001_refactor_variant_attributes_value_id.php"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Fixed migration uploaded" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Upload failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "=== NOW RUNNING MIGRATIONS ===" -ForegroundColor Cyan
Write-Host ""

plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "[OK] Migrations executed successfully!" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "[ERROR] Migration execution failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan view:clear && php artisan config:clear"

Write-Host ""
Write-Host "=== DEPLOYMENT COMPLETE ===" -ForegroundColor Green
