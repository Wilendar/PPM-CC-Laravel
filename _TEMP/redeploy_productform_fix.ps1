# HOTFIX: Re-deploy ProductForm.php (fixed $currentMode bug)
# Date: 2025-11-14

$HostidoKey = "D:\OneDrive - MPP TRADE\SSH\Hostido\HostidoSSHNoPass.ppk"
$LocalRoot = "D:\OneDrive - MPP TRADE\Skrypty\PPM-CC-Laravel"
$RemoteBase = "host379076@host379076.hostido.net.pl:domains/ppm.mpptrade.pl/public_html"

Write-Host "`n=== HOTFIX: RE-DEPLOY ProductForm.php ===" -ForegroundColor Cyan

# Deploy Livewire Component
Write-Host "`nDeploying ProductForm.php..." -ForegroundColor Yellow
pscp -i $HostidoKey -P 64321 `
    "$LocalRoot\app\Http\Livewire\Products\Management\ProductForm.php" `
    "${RemoteBase}/app/Http/Livewire/Products/Management/"

if ($LASTEXITCODE -eq 0) {
    Write-Host "  SUCCESS: ProductForm.php uploaded" -ForegroundColor Green
} else {
    Write-Host "  ERROR: Failed to upload" -ForegroundColor Red
    exit 1
}

# Clear caches
Write-Host "`nClearing caches..." -ForegroundColor Yellow
plink -ssh host379076@host379076.hostido.net.pl -P 64321 -i $HostidoKey -batch `
    "cd domains/ppm.mpptrade.pl/public_html && php artisan view:clear && php artisan cache:clear"

if ($LASTEXITCODE -eq 0) {
    Write-Host "  SUCCESS: Caches cleared" -ForegroundColor Green
} else {
    Write-Host "  WARNING: Cache clearing failed" -ForegroundColor Yellow
}

Write-Host "`n=== HOTFIX COMPLETE ===" -ForegroundColor Green
