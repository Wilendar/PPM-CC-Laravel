$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "=== RUNNING MIGRATION ===" -ForegroundColor Cyan
Write-Host ""

Write-Host "[1/2] Executing migration: drop has_variants column..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan migrate --force"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Migration executed successfully" -ForegroundColor Green
} else {
    Write-Host "[ERROR] Migration failed!" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[2/2] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
  "cd domains/ppm.mpptrade.pl/public_html && php artisan cache:clear && php artisan view:clear && php artisan config:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] Cache cleared" -ForegroundColor Green
} else {
    Write-Host "[WARNING] Cache clear failed (not critical)" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "=== MIGRATION COMPLETE ===" -ForegroundColor Green
Write-Host "has_variants column dropped from products table!" -ForegroundColor Cyan
