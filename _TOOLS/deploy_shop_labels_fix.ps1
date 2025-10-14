# SHOP LABELS BUG FIX - DEPLOYMENT SCRIPT
# Data: 2025-10-01
# Fix: Blade loop iteration + $refresh() dispatch

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== SHOP LABELS FIX DEPLOYMENT ===" -ForegroundColor Cyan
Write-Host "Deploying critical fix for shop labels reactivity..." -ForegroundColor Yellow

# 1. Upload ProductForm.php
Write-Host "`n[1/3] Uploading ProductForm.php..." -ForegroundColor Cyan
$LocalPHP = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\Management\ProductForm.php"
$RemotePHP = "domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php"
pscp -i $HostidoKey -P 64321 $LocalPHP host379076@host379076.hostido.net.pl:$RemotePHP

if ($LASTEXITCODE -eq 0) {
    Write-Host "ProductForm.php uploaded successfully!" -ForegroundColor Green
} else {
    Write-Host "FAILED to upload ProductForm.php" -ForegroundColor Red
    exit 1
}

# 2. Upload product-form.blade.php
Write-Host "`n[2/3] Uploading product-form.blade.php..." -ForegroundColor Cyan
$LocalBlade = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\management\product-form.blade.php"
$RemoteBlade = "domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/management/product-form.blade.php"
pscp -i $HostidoKey -P 64321 $LocalBlade host379076@host379076.hostido.net.pl:$RemoteBlade

if ($LASTEXITCODE -eq 0) {
    Write-Host "product-form.blade.php uploaded successfully!" -ForegroundColor Green
} else {
    Write-Host "FAILED to upload product-form.blade.php" -ForegroundColor Red
    exit 1
}

# 3. Clear cache
Write-Host "`n[3/3] Clearing cache..." -ForegroundColor Cyan
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "Cache cleared successfully!" -ForegroundColor Green
} else {
    Write-Host "FAILED to clear cache" -ForegroundColor Red
    exit 1
}

Write-Host "`n=== DEPLOYMENT COMPLETED ===" -ForegroundColor Green
Write-Host "`nTest URL: https://ppm.mpptrade.pl/admin/products/4/edit" -ForegroundColor Yellow
Write-Host "`nPlease verify:" -ForegroundColor Cyan
Write-Host "1. Add shop -> label appears" -ForegroundColor White
Write-Host "2. Remove shop (X button) -> label DISAPPEARS IMMEDIATELY" -ForegroundColor White
Write-Host "3. Save -> shop is NOT in database" -ForegroundColor White
