# CRITICAL FIX - Save Logic + UI Reactivity
# Date: 2025-10-01
# Fix: array_diff to filter $shopsToRemove + force array reference

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== CRITICAL SAVE LOGIC FIX DEPLOYMENT ===" -ForegroundColor Cyan
Write-Host "Deploying fix for shops being saved despite removal..." -ForegroundColor Yellow

# Upload ProductForm.php
Write-Host "`n[1/2] Uploading ProductForm.php..." -ForegroundColor Cyan
$LocalPHP = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\Management\ProductForm.php"
$RemotePHP = "domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php"
pscp -i $HostidoKey -P 64321 $LocalPHP host379076@host379076.hostido.net.pl:$RemotePHP

if ($LASTEXITCODE -eq 0) {
    Write-Host "ProductForm.php uploaded successfully!" -ForegroundColor Green
} else {
    Write-Host "FAILED to upload ProductForm.php" -ForegroundColor Red
    exit 1
}

# Clear cache
Write-Host "`n[2/2] Clearing cache..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "Cache cleared successfully!" -ForegroundColor Green
} else {
    Write-Host "FAILED to clear cache" -ForegroundColor Red
    exit 1
}

Write-Host "`n=== DEPLOYMENT COMPLETED ===" -ForegroundColor Green
Write-Host "`nFIXES DEPLOYED:" -ForegroundColor Yellow
Write-Host "1. Save logic now filters shopsToRemove using array_diff()" -ForegroundColor White
Write-Host "2. Save logic checks isset(shopData) before creating" -ForegroundColor White
Write-Host "3. UI reactivity - force new array reference with spread operator" -ForegroundColor White
Write-Host "4. Added extensive debug logging" -ForegroundColor White

Write-Host "`nTest URL: https://ppm.mpptrade.pl/admin/products/4/edit" -ForegroundColor Yellow
Write-Host "`nPLEASE TEST:" -ForegroundColor Cyan
Write-Host "SCENARIO 1 (Pending shop):" -ForegroundColor White
Write-Host "  - Add new shop -> Remove -> Save -> Check DB (should NOT exist)" -ForegroundColor Gray
Write-Host "SCENARIO 2 (DB shop):" -ForegroundColor White
Write-Host "  - Remove shop -> Add back -> Remove again -> Save -> Check DB" -ForegroundColor Gray
