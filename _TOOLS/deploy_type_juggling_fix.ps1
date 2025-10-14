# CRITICAL FIX - Type Juggling in Shop IDs
# Date: 2025-10-01
# Root Cause: Mixed int/string types in $exportedShops causing comparison failures

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"

Write-Host "`n=== TYPE JUGGLING FIX DEPLOYMENT ===" -ForegroundColor Cyan
Write-Host "ROOT CAUSE: exportedShops had mixed int/string types!" -ForegroundColor Yellow
Write-Host "Example: [1,4,2,`"3`"] - note shopId 3 is string!" -ForegroundColor Gray

Write-Host "`n[1/2] Uploading ProductForm.php with type normalization..." -ForegroundColor Cyan
$LocalPHP = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel\app\Http\Livewire\Products\Management\ProductForm.php"
$RemotePHP = "domains/ppm.mpptrade.pl/public_html/app/Http/Livewire/Products/Management/ProductForm.php"
pscp -i $HostidoKey -P 64321 $LocalPHP host379076@host379076.hostido.net.pl:$RemotePHP

if ($LASTEXITCODE -eq 0) {
    Write-Host "ProductForm.php uploaded successfully!" -ForegroundColor Green
} else {
    Write-Host "FAILED to upload ProductForm.php" -ForegroundColor Red
    exit 1
}

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
Write-Host "1. removeFromShop() - array_filter with (int) normalization" -ForegroundColor White
Write-Host "2. addToShops() - type-safe loops with normalization" -ForegroundColor White
Write-Host "3. Save logic - array_map('intval') before array_diff()" -ForegroundColor White
Write-Host "4. Extensive debug logging to track types" -ForegroundColor White

Write-Host "`nTest URL: https://ppm.mpptrade.pl/admin/products/4/edit" -ForegroundColor Yellow
Write-Host "`nTEST BOTH SCENARIOS:" -ForegroundColor Cyan
Write-Host "1. Add new shop -> Remove -> Label SHOULD DISAPPEAR -> Save -> NOT in DB" -ForegroundColor Gray
Write-Host "2. Remove DB shop -> Add back -> Remove -> Label SHOULD DISAPPEAR -> Save -> Deleted" -ForegroundColor Gray

Write-Host "`nMONITOR LOGS:" -ForegroundColor Yellow
Write-Host "Run: pwsh _TOOLS/check_logs.ps1" -ForegroundColor Gray
Write-Host "Look for: 'exportedShops_BEFORE', 'exportedShops_AFTER', 'exportedShops_types'" -ForegroundColor Gray
