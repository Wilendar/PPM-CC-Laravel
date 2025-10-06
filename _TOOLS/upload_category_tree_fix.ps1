# Upload category-tree-ultra-clean with dropdown overflow fix
$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== UPLOADING CATEGORY TREE DROPDOWN FIX ===" -ForegroundColor Cyan

# Upload fixed file
Write-Host "`n[1/2] Uploading category-tree-ultra-clean.blade.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\resources\views\livewire\products\categories\category-tree-ultra-clean.blade.php" `
    host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html/resources/views/livewire/products/categories/category-tree-ultra-clean.blade.php

if ($LASTEXITCODE -ne 0) {
    Write-Host "ERROR: Failed to upload file" -ForegroundColor Red
    exit 1
}

# Clear cache
Write-Host "`n[2/2] Clearing cache..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "`n=== SUCCESS ===" -ForegroundColor Green
    Write-Host "✓ Dropdown overflow fix applied (overflow-y: visible)" -ForegroundColor Green
    Write-Host "`nTest URL:" -ForegroundColor Cyan
    Write-Host "  - Categories: https://ppm.mpptrade.pl/admin/products/categories" -ForegroundColor White
} else {
    Write-Host "`nERROR: Cache clear failed" -ForegroundColor Red
    exit 1
}